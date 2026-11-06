importScripts('vendor/spelling/typo.js');

const dictionaries = new Map();
function loadDictionary(language) {
  const locale = language === 'en-GB' ? 'en-GB' : 'en-US';
  if (!dictionaries.has(locale)) {
    const loading = Promise.all(['aff', 'dic'].map(async extension => {
      const response = await fetch(`vendor/spelling/${locale}.${extension}`);
      if (!response.ok) throw new Error('Dictionary unavailable');
      return response.text();
    })).then(([aff, dic]) => new Typo(locale, aff, dic));
    dictionaries.set(locale, loading);
    loading.catch(() => dictionaries.delete(locale));
  }
  return dictionaries.get(locale);
}

self.onmessage = async ({ data }) => {
  const { version, text, language } = data;
  try {
    const dictionary = await loadDictionary(language);
    if (data.type === 'suggest') {
      const { word, offset } = data;
      if (typeof word !== 'string' || word.length > 40 || text.slice(offset, offset + word.length) !== word) throw new Error('Outdated word');
      self.postMessage({ type: 'suggest', version, text, offset, word, suggestions: dictionary.suggest(word.replace(/’/g, "'"), 4) });
      return;
    }
    const matches = [];
    const issues = [];
    const checked = new Map();
    const suggested = new Set();
    for (const token of text.matchAll(/[\p{L}]+(?:['’][\p{L}]+)*/gu)) {
      const word = token[0];
      const normalized = word.replace(/’/g, "'");
      if (word.length < 2 || word.length > 40) continue;
      if (!checked.has(normalized)) checked.set(normalized, dictionary.check(normalized));
      if (checked.get(normalized)) continue;
      issues.push({ word, offset: token.index });
      if (matches.length < 8 && !suggested.has(normalized)) {
        matches.push({ word, offset: token.index, suggestions: dictionary.suggest(normalized, 4) });
        suggested.add(normalized);
      }
    }
    self.postMessage({ version, text, matches, issues });
  } catch (error) {
    self.postMessage({ version, text, error: true, ...(data.type === 'suggest' ? { type: 'suggest', offset: data.offset, word: data.word } : {}) });
  }
};
