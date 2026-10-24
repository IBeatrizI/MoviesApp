# MoviesApp — Cinemateca

**A web application for discovering movies and TV series, exploring genres, and managing personal favorites.**

MoviesApp (Cinemateca) is a personal/academic full-stack project built with **Laravel 10**, **PHP**, **MySQL**, **Blade**, **JavaScript**, and the **TMDB API**. This repository focuses exclusively on the **web application**.

> **Project status:** Functional development version for local use. Some security and data-import improvements are still required before a public production deployment.

## Features

- **Movie and TV series catalogs** with posters, overviews, release information, and ratings where available.
- **Genre browsing and filtering** for movies and series, including categories such as Action, Comedy, Drama, and more.
- **Title details in a responsive modal** with clickable genre tags that filter the catalog.
- **User registration and login**.
- **Personal favorites**, with separate support for movies and TV series.
- **Search and sorting** of the currently displayed catalog page.
- **Pagination** for browsing catalog results.
- **Multilingual content**: Portuguese (`pt`), English (`en`), and Spanish (`es`).
- **REST API** implemented in Laravel, used by the web frontend.
- **Responsive, cinema-inspired interface** with a dark color palette.

### Data integration

- **Movies:** Imported from TMDB into MySQL during the existing database migrations; movie listings are read from the local database.
- **TV series:** Retrieved from TMDB through the Laravel backend, with application-level caching.
- **TMDB credentials:** Stored in the local `.env` file, never committed to Git.

**Note:** The current project does **not** include a verified daily synchronization job for both catalogs. Automated, incremental importing is planned as a future improvement.

## Tech stack

| Layer | Technologies |
| --- | --- |
| Backend | PHP 8.1+, Laravel 10 |
| Frontend | Laravel Blade, HTML, CSS, vanilla JavaScript |
| Database | MySQL |
| External data | The Movie Database (TMDB) API |
| HTTP/API | Laravel REST endpoints, Laravel HTTP client |
| Local development | Composer, PHP Artisan, optional XAMPP for MySQL |

The frontend assets are served directly from `public/assets/`. **Node.js, Vite, and `npm run build` are not required to display the current catalog interface.**

## Project structure

```text
movies-app/
├── app/
│   ├── Http/Controllers/Api/  # API controllers
│   └── Models/                # Eloquent models
├── config/                    # Laravel configuration and TMDB token mapping
├── database/
│   └── migrations/            # Database tables and initial TMDB import
├── public/
│   └── assets/
│       ├── css/catalog.css    # Catalog styles
│       └── js/catalog.js      # Frontend interactions
├── resources/
│   └── views/catalog.blade.php # Web interface
├── routes/
│   ├── api.php                # REST endpoints
│   └── web.php                # Main web route
├── tests/                     # Laravel tests
├── .env.example
└── composer.json
```

## Requirements

- **PHP 8.1 or newer**, with the extensions required by Laravel and this project (including `curl`, `pdo_mysql`, `mbstring`, `openssl`, `xml`, and `fileinfo`).
- **Composer**.
- **MySQL** (for example, from XAMPP).
- An Internet connection and a **TMDB API Read Access Token** for the initial import and TV series requests.

## Local setup

The project **does not need to be placed inside XAMPP's `htdocs` folder**. You can use XAMPP for MySQL and start the Laravel web server separately.

### 1. Clone and install dependencies

```bash
git clone https://github.com/YOUR_USERNAME/MoviesApp.git
cd MoviesApp
composer install
```

Replace `YOUR_USERNAME` with your GitHub username. These commands assume that the Laravel project files are at the root of the repository; otherwise, enter the Laravel subfolder first.

### 2. Configure environment variables

Create `.env` from the example:

**Windows PowerShell**

```powershell
Copy-Item .env.example .env
```

**macOS/Linux**

```bash
cp .env.example .env
```

Update the relevant values in `.env`:

```dotenv
APP_NAME=Cinemateca
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moviesapp
DB_USERNAME=root
DB_PASSWORD=

TMDB_TOKEN=your_tmdb_read_access_token
```

The database credentials above are **examples for a local environment**; use your own configuration. Create an empty `moviesapp` database in MySQL before continuing.

Generate the Laravel application key:

```bash
php artisan key:generate
```

### 3. Create and populate the database

```bash
php artisan migrate
```

**Important:** The current migrations retrieve genre and movie data from TMDB. This step requires a valid token and Internet access and **may take a while**, because it performs multiple requests. If TMDB is unavailable or rate-limits the requests, database initialization may fail. The import process should eventually be separated from schema migrations.

### 4. Start the web application

```bash
php artisan serve
```

Open **http://127.0.0.1:8000** and register an account to browse the catalog.

## API overview

All API routes are prefixed with `/api`.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/api/genres?language=pt` | Movie genres |
| `GET` | `/api/movie?language=pt&page=1&with_genres=0` | Paginated movies; `0` means all genres |
| `GET` | `/api/movie/genres?language=pt` | Movies grouped by genre |
| `GET` | `/api/series/genres?language=pt` | TV series genres |
| `GET` | `/api/series?language=pt&page=1&with_genres=0` | Paginated TV series |
| `POST` | `/api/user/register` | Create an account |
| `POST` | `/api/user/login` | Log in |
| `GET` | `/api/user/favorites?user_id=1&language=pt` | Saved movies |
| `POST` | `/api/user/favorites/save` | Save a movie to favorites |
| `POST` | `/api/user/favorites/delete` | Remove a movie from favorites |
| `GET` | `/api/user/favorites/series?user_id=1&language=pt` | Saved TV series |
| `POST` | `/api/user/favorites/series/save` | Save a TV series |
| `POST` | `/api/user/favorites/series/delete` | Remove a TV series |

The write endpoints receive the relevant `user_id` and `movie_id` or `series_id` in the request body. Consult `routes/api.php` for the full list, including `/api/user/favorites/find`.

## Tests

The repository includes Laravel tests under `tests/`. Once the environment and database configuration are ready, run:

```bash
php artisan test
```

**Test status:** Source-level checks have been performed on the frontend revision, but the entire test suite and a full deployment have not been verified. No CI/CD pipeline is currently included.

## Known limitations and planned improvements

- [ ] Replace client-supplied `user_id` authorization with **authenticated, server-side access control** (e.g., Laravel Sanctum).
- [ ] Move the initial TMDB import out of database migrations and implement reliable, incremental synchronization.
- [ ] Add detailed movie and series pages, trailers, casts, seasons, and episodes.
- [ ] Add watchlists and watch-progress tracking.
- [ ] Improve global search (current search and sorting apply to the loaded catalog page).
- [ ] Expand automated tests and add a GitHub Actions workflow.
- [ ] Prepare a production deployment, including HTTPS, rate limiting, error handling, and secure session management.

## Screenshots

Screenshots of the web application will be added to the repository after the local UI has been validated.

## Data source and attribution

This product uses the **TMDB API** but is **not endorsed or certified by TMDB**.

Movie and TV metadata and images are provided by [The Movie Database (TMDB)](https://www.themoviedb.org/). Refer to [TMDB API documentation](https://developer.themoviedb.org/) for API usage conditions and attribution requirements. If publishing this project publicly, include the applicable approved TMDB logo and attribution in the deployed interface.

## Security note

This is a **portfolio/development project, not a production-ready authentication implementation**. In the current version, favorite endpoints accept a `user_id` provided by the client and do not yet enforce a secure, authenticated identity for those operations. **Do not deploy publicly with real user accounts until authorization and session handling have been reviewed and fixed.**

Never commit `.env`, real credentials, database exports containing personal information, or `vendor/`. The project `.gitignore` already excludes `.env` and `vendor/`.
