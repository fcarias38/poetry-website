"""Deploy the notification feature using the existing private FTP configuration.

Backs up every changed remote file, verifies uploaded bytes, and restores files
if an upload fails. Credentials are read in memory and never printed.
"""
import ftplib
import hashlib
import io
import json
from pathlib import Path
import ssl
import socket
import sys
import tempfile

ROOT = Path(__file__).resolve().parent.parent
FILES = [
    'notifications.php', 'admin_notifications.php', 'admin-notifications.js',
    'admin.css', 'admin_session.php', 'save_comment.php', 'update_likes.php',
    'track_share.php', 'home.shtml', 'poem.php', 'dashboard.php', '.htaccess',
]


def connect():
    config = json.loads((ROOT / '.vscode' / 'sftp.json').read_text(encoding='utf-8-sig'))
    ftp = ftplib.FTP_TLS(context=ssl.create_default_context(), timeout=45)
    # The configured site alias presents Bluehost's wildcard certificate.
    # Use its canonical host only after confirming both names reach the same IP.
    host = 'box5312.bluehost.com'
    if socket.gethostbyname(host) != socket.gethostbyname(config['host']):
        raise RuntimeError('Configured FTP host no longer matches the verified Bluehost server')
    ftp.connect(host, int(config.get('port', 21)))
    ftp.login(config['username'], config['password'])
    ftp.prot_p()
    ftp.cwd(config.get('remotePath', './'))
    return ftp


def read_remote(ftp, name):
    buffer = io.BytesIO()
    ftp.retrbinary('RETR ' + name, buffer.write)
    return buffer.getvalue()


def main():
    files = sys.argv[1:] or FILES
    if any(name not in FILES for name in files):
        raise ValueError('Only known notification feature files can be deployed')
    backup = Path(tempfile.mkdtemp(prefix='think-room-notification-deploy-'))
    ftp = connect()
    originals = {}
    attempted = []
    try:
        # Finish the complete backup before overwriting any remote file.
        for name in files:
            try:
                originals[name] = read_remote(ftp, name)
                (backup / name).write_bytes(originals[name])
            except ftplib.error_perm as error:
                if not str(error).startswith('550'):
                    raise
                # Confirm absence rather than treating permission errors as absence.
                if name in [Path(entry).name for entry in ftp.nlst()]:
                    raise
                originals[name] = None
        (backup / 'manifest.json').write_text(json.dumps({name: data is not None for name, data in originals.items()}, indent=2))
        print('Remote backups saved to ' + str(backup), flush=True)
        for name in files:
            data = (ROOT / name).read_bytes()
            attempted.append(name)
            ftp.storbinary('STOR ' + name, io.BytesIO(data))
            actual = read_remote(ftp, name)
            if hashlib.sha256(actual).digest() != hashlib.sha256(data).digest():
                raise RuntimeError('Uploaded bytes differ for ' + name)
            print('Verified ' + name, flush=True)
        print('DEPLOYED: all notification files match local source.', flush=True)
    except Exception:
        print('Upload failed; restoring attempted files.', flush=True)
        ftp.close()
        ftp = connect()
        for name in reversed(attempted):
            if originals[name] is None:
                ftp.delete(name)
            else:
                ftp.storbinary('STOR ' + name, io.BytesIO(originals[name]))
        print('Remote files restored.', flush=True)
        raise
    finally:
        ftp.quit()


if __name__ == '__main__':
    main()
