import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const I18n = require('../../Core/SWUCardI18n.js');

const M = I18n.indexManifest({
  locale: 'es',
  WebpImages: ['SOR_005', 'SOR_005_back', 'SOR_010'],
  concat: ['SOR_005', 'SOR_010'],
  crops: ['SOR_005'],
});
const ROOT = '/TCGEngine/AppCore/SWU/Images';

test('languages and normalisation', () => {
  assert.deepEqual(I18n.LANGS, ['en', 'es', 'it', 'fr']);
  assert.equal(I18n.normalizeLang('ES'), 'es');
  assert.equal(I18n.normalizeLang('de'), null);
  assert.equal(I18n.normalizeLang(null), null);
});

test('a card in the manifest gets the localized url', () => {
  assert.equal(I18n.rewriteUrl(`${ROOT}/concat/SOR_010.webp`, 'es', M), `${ROOT}/i18n/es/concat/SOR_010.webp`);
  assert.equal(I18n.rewriteUrl(`${ROOT}/WebpImages/SOR_005_back.webp`, 'es', M), `${ROOT}/i18n/es/WebpImages/SOR_005_back.webp`);
  assert.equal(I18n.rewriteUrl(`${ROOT}/crops/SOR_005_cropped.png`, 'es', M), `${ROOT}/i18n/es/crops/SOR_005_cropped.png`);
});

test('the manifest is checked per folder', () => {
  // SOR_010 has a full card and a tile but no crop
  assert.equal(I18n.rewriteUrl(`${ROOT}/crops/SOR_010_cropped.png`, 'es', M), `${ROOT}/crops/SOR_010_cropped.png`);
  // SOR_005_back has a full card but no tile
  assert.equal(I18n.rewriteUrl(`${ROOT}/concat/SOR_005_back.webp`, 'es', M), `${ROOT}/concat/SOR_005_back.webp`);
});

test('a card missing from the manifest stays English', () => {
  assert.equal(I18n.rewriteUrl(`${ROOT}/concat/SOR_999.webp`, 'es', M), `${ROOT}/concat/SOR_999.webp`);
});

test('english, no manifest, or mock art stays English', () => {
  assert.equal(I18n.rewriteUrl(`${ROOT}/concat/SOR_010.webp`, 'en', M), `${ROOT}/concat/SOR_010.webp`);
  assert.equal(I18n.rewriteUrl(`${ROOT}/concat/SOR_010.webp`, 'es', null), `${ROOT}/concat/SOR_010.webp`);
  const withMock = I18n.indexManifest({ concat: ['mock_HMW_004'] });
  assert.equal(I18n.rewriteUrl(`${ROOT}/concat/mock_HMW_004.webp`, 'es', withMock), `${ROOT}/concat/mock_HMW_004.webp`);
});

test('an already-localized url is switched or reverted', () => {
  const es = `${ROOT}/i18n/es/concat/SOR_010.webp`;
  assert.equal(I18n.rewriteUrl(es, 'en', null), `${ROOT}/concat/SOR_010.webp`);
  assert.equal(I18n.rewriteUrl(es, 'it', I18n.indexManifest({ concat: ['SOR_010'] })), `${ROOT}/i18n/it/concat/SOR_010.webp`);
  assert.equal(I18n.rewriteUrl(es, 'fr', I18n.indexManifest({ concat: [] })), `${ROOT}/concat/SOR_010.webp`);
});

test('relative, absolute-host and query-string forms are preserved', () => {
  assert.equal(I18n.rewriteUrl('./AppCore/SWU/Images/concat/SOR_010.webp', 'es', M), './AppCore/SWU/Images/i18n/es/concat/SOR_010.webp');
  assert.equal(I18n.rewriteUrl('https://x.test/TCGEngine/AppCore/SWU/Images/concat/SOR_010.webp?v=3', 'es', M),
    'https://x.test/TCGEngine/AppCore/SWU/Images/i18n/es/concat/SOR_010.webp?v=3');
});

test('urls outside the SWU card corpus are untouched', () => {
  for (const u of ['/TCGEngine/Assets/CardBack.webp', './GrandArchiveSim/concat/abc.webp', '', undefined, null]) {
    assert.equal(I18n.rewriteUrl(u, 'es', M), u);
  }
});

test('a folder/extension mismatch stays English even if the stem is in the manifest', () => {
  // WebpImages only ever holds "<stem>.webp" — a ".png" claiming to be in that folder can't be a real
  // localized file (the pipeline never writes one), so it must not be rewritten even though "SOR_005"
  // is present in the WebpImages manifest bucket.
  assert.equal(I18n.rewriteUrl(`${ROOT}/WebpImages/SOR_005.png`, 'es', M), `${ROOT}/WebpImages/SOR_005.png`);
  // crops only ever holds "<stem>_cropped.png" — a plain ".webp" in crops (no _cropped suffix) must
  // stay English even though "SOR_005" is present in the crops manifest bucket.
  assert.equal(I18n.rewriteUrl(`${ROOT}/crops/SOR_005.webp`, 'es', M), `${ROOT}/crops/SOR_005.webp`);
});

test('shapeMatchesFolder ties extension/suffix to folder', () => {
  assert.equal(I18n.shapeMatchesFolder('WebpImages', '', 'webp'), true);
  assert.equal(I18n.shapeMatchesFolder('concat', '', 'webp'), true);
  assert.equal(I18n.shapeMatchesFolder('crops', '_cropped', 'png'), true);
  assert.equal(I18n.shapeMatchesFolder('WebpImages', '', 'png'), false);
  assert.equal(I18n.shapeMatchesFolder('concat', '_cropped', 'webp'), false);
  assert.equal(I18n.shapeMatchesFolder('crops', '', 'webp'), false);
  assert.equal(I18n.shapeMatchesFolder('crops', '', 'png'), false);
});

test('css url() values are rewritten', () => {
  const css = `width: 10px; background-image:url(${ROOT}/concat/SOR_010.webp)`;
  assert.equal(I18n.rewriteCss(css, 'es', M), `width: 10px; background-image:url(${ROOT}/i18n/es/concat/SOR_010.webp)`);
  const quoted = `background-image: url("${ROOT}/WebpImages/SOR_005.webp")`;
  assert.equal(I18n.rewriteCss(quoted, 'es', M), `background-image: url("${ROOT}/i18n/es/WebpImages/SOR_005.webp")`);
  assert.equal(I18n.rewriteCss('color: red', 'es', M), 'color: red');
});
