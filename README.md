# Battle Quiz (`mod_knowledgebattle`)

[![Moodle](https://img.shields.io/badge/Moodle-4.4%2B%20%7C%205.0%2B-orange.svg)](https://moodle.org)
[![PHP](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%2B-blue.svg)](https://php.net)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-green.svg)](LICENSE)

**Battle Quiz (`mod_knowledgebattle`)** is an asynchronous, gamified quiz activity plugin for Moodle. It transforms routine study into a competitive and cooperative learning experience inspired by popular quiz duel games (*Trivia Crack*, *Duolingo*).

Students can challenge their peers to asynchronous PvP knowledge duels or compete against an intelligent AI Master bot. Teachers can automatically generate contextual questions from course content using leading Large Language Models (LLMs).

---

## 🌟 Key Features

* **⚔️ Asynchronous PvP Duels**: Students can challenge specific peers or join quick matchmaking. Each participant answers rounds at their own pace with strict time limits per question and W.O. timeouts.
* **🤖 AI Master (Bot Opponent)**: Provides instant matchmaking for students in low-activity cohorts or asynchronous courses, ensuring learners can always battle and practice.
* **🧠 Multi-Provider AI Question Generation**: Generate high-quality multiple-choice questions directly from course sections, custom topics, or resource texts using:
  * **OpenAI** (GPT-4o, GPT-4o-mini)
  * **Google Gemini** (Gemini 1.5/2.5 Flash & Pro)
  * **Anthropic Claude** (Claude 3.5 Sonnet, Claude 3 Haiku)
  * **DeepSeek** (DeepSeek-V3, DeepSeek Chat)
  * **Groq** (Ultra-fast Llama 3 models)
  * **OpenRouter** (Aggregated access to dozens of open-source models)
  * **Local LLM** (Self-hosted Ollama, vLLM, or LocalAI endpoints)
* **📋 Question Management & Curation**: Teachers can review, edit, approve, or discard AI-generated questions before they enter the active match pool.
* **🏆 Leaderboard & Streaks**: Real-time course ranking displaying points, wins, losses, win rates, and current winning streaks.
* **📊 Teacher Analytics Report**: Dedicated class performance dashboard with question difficulty analysis, discrimination index, student participation rates, and match logs.
* **📱 Moodle App Compatible**: Native mobile support declarations via `db/mobile.php`.
* **🔒 Privacy & GDPR Ready**: Full implementation of Moodle Privacy API (`\core_privacy\local\metadata\provider`) supporting data export and deletion.

---

## 📋 Requirements

* **Moodle**: 4.4+ (Build 2024042200 or newer), fully compatible with Moodle 4.5 and 5.0+.
* **PHP**: 8.1, 8.2, or 8.3+.
* **Database**: MySQL 8.0+, MariaDB 10.4+, or PostgreSQL 13+.
* **Internet Connectivity**: Required if using cloud-based AI providers (OpenAI, Gemini, Anthropic, DeepSeek, Groq, OpenRouter). Self-hosted models (Ollama/vLLM) run entirely locally.

---

## 🚀 Installation

### Option 1: Via Moodle Marketplace (Recommended)
1. Log in to your Moodle site as an administrator.
2. Go to **Site administration > Plugins > Install plugins**.
3. Search for **Battle Quiz** (`knowledgebattle`) in the Moodle Marketplace directory and click **Install**.
4. Follow the on-screen database upgrade steps.

### Option 2: Via ZIP Upload
1. Download the latest release ZIP archive (`knowledgebattle.zip`).
2. Navigate to **Site administration > Plugins > Install plugins**.
3. Upload `knowledgebattle.zip` and select **Activity module (`mod`)** as plugin type.
4. Confirm installation and complete the database upgrade.

### Option 3: Via Git
Clone the repository directly into your Moodle `mod/knowledgebattle` folder:

```bash
cd /path/to/moodle/mod
git clone https://github.com/juniorcastr/knowledgebattle.git knowledgebattle
cd knowledgebattle
git checkout master
```

Then visit your site's **Site administration > Notifications** to trigger the database installation.

---

## ⚙️ Configuration

### 1. Global AI Settings (Site Administrator)
Navigate to:  
**Site administration > Plugins > Activity modules > Battle Quiz** (`admin/settings.php?section=modsettingknowledgebattle`)

* **Default AI Provider**: Choose your preferred global provider (Gemini, OpenAI, Claude, DeepSeek, Groq, OpenRouter, Local LLM).
* **API Keys**: Enter the respective API key(s) for the providers you wish to enable.
* **Local LLM Base URL**: Set your Ollama / vLLM endpoint URL (e.g., `http://localhost:11434/v1`).
* **Anti-Flood Limit**: Rate limiting protection for question generation requests.
* **Question Cache TTL**: Cache duration for generated content to optimize token consumption.

### 2. Activity Instance Settings (Course Teacher)
When adding or editing a **Battle Quiz** in a course:
* **General**: Name and description.
* **Content Configuration**: Select content source (Course Section, Specific Resource, Custom Topic, or Question Bank).
* **Battle Rules**: Number of questions per duel (default: 5), time limit per question (default: 30s), W.O. timeout (hours).
* **Scoring Rules**: Points awarded for victory, draw, and defeat; allow/disallow negative scores.
* **Limits & Bot**: Daily battle limits per student, enable/disable AI Bot challenges.
* **Grading & Completion**: Integrated with Moodle Gradebook and Activity Completion (e.g., require minimum battles or wins).

---

## ⏱️ Scheduled Tasks

The plugin registers a scheduled task:
* `\mod_knowledgebattle\task\check_expired_matches` (runs every 15 minutes by default).
  * Automatically resolves pending matches where an opponent exceeded the configured W.O. timeout window, declaring victory for the active player.

You can inspect or trigger this task manually under:  
**Site administration > Server > Tasks > Scheduled tasks**.

---

## 🧪 Testing

PHPUnit unit tests are provided in `tests/`:
```bash
# Run PHPUnit test suite for mod_knowledgebattle
vendor/bin/phpunit mod_knowledgebattle_testcase mod/knowledgebattle/tests/
```

---

## 📜 License

Licensed under the **GNU General Public License, version 3 or later (GPL-3.0-or-later)**.  
See the [LICENSE](LICENSE) file for the full license text.
