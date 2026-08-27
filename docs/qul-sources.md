# QUL sources

How Bayan fills the library. QUL’s published contract is [download JSON/SQLite](https://qul.tarteel.ai/docs/downloading-data). A public REST API is [not shipped](https://qul.tarteel.ai/docs/api). Some `/api/v1` routes exist anyway; we only call chapters.

Do not hotlink recitation CDNs (`audio-cdn.tarteel.ai`, `audio.qurancdn.com`) when rendering. Recitation dumps include `audio_url` so we can copy mp3s into local storage once.

| Kind | Bayan | Source type | Operator | Stored dump path |
| --- | --- | --- | --- | --- |
| Surah metadata | `Surah` | QUL API `GET /api/v1/chapters` | Filament → `ImportSurahs` | — |
| Quran script (Uthmani) | `Ayah.uthmani_text` | JSON dump | Filament upload | `imports/script.json` |
| Translation | `TranslationSource` + `Translation` | JSON dump (`{"1:1":{"t":"...","f":{...}}}`) | Filament upload + name (slug optional) | `imports/translations/{slug}.json` |
| Reciter URL list | `Reciter` | JSON dump (`{"1:1":{"audio_url":"...","segments":[...]}}`) | Filament upload + name | `imports/recitations/{id}.json` |
| Reciter mp3s | `AyahAudio` + library disk | Dump `audio_url` **at import only** | One `DownloadReciterSurahAudio` job per surah | `audio/{reciter_id}/{surah}/{ayah}.mp3` |
| Backgrounds | `Background` | Pexels (unchanged) | Filament | — |

Re-upload always overwrites the dump at the stable path. Reciter mp3s that already exist locally are skipped so a failed surah can resume.

Ayah recitations only — not surah numbers and not gapless surah recitations. Dumps have no source metadata. Re-import matches a translation by slug and a reciter by name. Gapless surah dumps have no per-ayah `audio_url` and will be rejected.
