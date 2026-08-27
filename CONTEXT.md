# Bayan

Bayan renders Quran video clips from a local library of surahs, Arabic text, translations, reciter audio, and backgrounds.

## Language

**QUL**:
Tarteel’s Quranic Universal Library. Downloadable JSON dumps plus a small unofficial chapters API. Not a general production API.
_Avoid_: Tarteel (the org and audio CDN), Quran.com

**Dump**:
A JSON file downloaded from QUL resources and stored on the library disk. Re-uploading replaces the file at a stable path.
_Avoid_: export, dataset file, package

**Surah**:
A Quran chapter, numbered 1–114, with English and Arabic names and an ayah count.
_Avoid_: chapter (except QUL’s API field names)

**Ayah**:
A verse identified by surah number plus ayah number. Arabic display text is Uthmani Hafs stored on the ayah.
_Avoid_: verse (except QUL `verse_key`)

**Translation source**:
One published translation (for example Saheeh International), identified by its slug.
_Avoid_: translator, edition, QUL translation id

**Translation**:
The stored plain text of one ayah in one translation source. Footnotes from QUL dumps are not stored.
_Avoid_: meaning, tafsir

**Recitation**:
A QUL ayah-by-ayah JSON dump: verse keys mapping to `audio_url` (and optional word `segments`). Reciter identity is not in the file.
_Avoid_: reciter dump, gapless/surah recitation

**Reciter**:
Bayan’s local row for one recitation, created by importing a dump plus a display name. Re-import with the same name replaces it.
_Avoid_: QUL Recitation (the dump), artist

**Ayah audio**:
A locally stored mp3 and duration for one ayah of one reciter. Runtime playback never uses Tarteel’s CDN.
_Avoid_: stream, hotlink
