# carbonai

carbonai est une application web de calcul et de suivi de l'empreinte carbone des entreprises. Elle transforme les informations renseignees par une organisation en resultats carbone, rapports et recommandations d'action.

## Fonctionnalites

- Configuration d'une entreprise et de son calcul carbone
- Generation de formulaires adaptes aux donnees a collecter
- Calcul et consultation des resultats par categorie d'emissions
- Generation de rapports carbone telechargeables
- Generation de recommandations pour reduire les emissions
- Historique des calculs
- Espace administrateur pour gerer les utilisateurs et les facteurs d'emission

## Technologies

- PHP 8.2+
- Laravel 12
- Blade, Tailwind CSS et Vite
- SQLite ou autre base de donnees compatible Laravel
- n8n pour orchestrer les agents et les workflows d'IA

## Architecture n8n

Laravel prepare les donnees et communique avec les workflows n8n via des webhooks HTTP. Les agents n8n sont responsables de :

- generer le formulaire de collecte des donnees carbone
- produire le rapport carbone
- proposer des recommandations de reduction des emissions

Les URLs des workflows sont configurees dans le fichier `.env` :

```env
N8N_GENERATE_FORM_URL=https://votre-instance-n8n/webhook/generate-form
N8N_GENERATE_REPORT_URL=https://votre-instance-n8n/webhook/generate-report
N8N_GENERATE_RECOMMENDATIONS_URL=https://votre-instance-n8n/webhook/generate-recommendations
```

En l'absence d'URL pour la generation du formulaire, l'application utilise un mode simule local. Les workflows n8n doivent retourner une reponse JSON conforme aux donnees attendues par l'application.

## Installation

### Prerequis

- PHP 8.2 ou superieur
- Composer
- Node.js et npm

### Demarrage

```bash
git clone https://github.com/<votre-utilisateur>/carbonai.git
cd carbonai
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

L'application est ensuite accessible a l'adresse `http://127.0.0.1:8000`.

Pour le developpement avec Vite, utilisez `npm run dev` dans un second terminal.

## Tests

```bash
php artisan test
```

## Licence

Ce projet est distribue sous licence MIT.
