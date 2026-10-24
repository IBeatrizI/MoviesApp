# MoviesApp 

## Installation Process
1. Install XAMPP.
2. Install Laravel -> https://laravel.com/docs/10.x/installation
3. Navigate to the htdocs folder in XAMPP and unzip the project there.
4. Open the file c:\Windows\System32\Drivers\etc\hosts with a text editor and check if this line exists: 127.0.0.1 localhost. If it doesn't exist, add it.
5. Open XAMPP and start Apache and MySQL.
6. In the terminal, navigate to the project folder and run the command: php artisan migrate. Wait for the command to finish. This process takes some time as the database is being populated using the original API, rather than a manual import.

## API Documentation
To use the API, simply use the URL http://127.0.0.1:8000/api/ and one of the methods listed below. The supported languages are: en, pt, and es.

## Movies
GET /genres: Requires language. Returns the list of genres.
GET /movie: Requires language, page, with_genres. Returns the list of movies that match these parameters.
GET /movies/genres: Requires language. Returns a list containing objects, where each object has the name of a category and a list of movies.

## Users
POST /user/register: Requires username, email, password. Registers a user.
POST /user/login: Requires username or email and password. Validates the login.
POST /user/favorites/save: Requires user_id and movie_id. Saves a movie to favorites.
POST /user/favorites/delete: Requires user_id and movie_id. Removes a movie from favorites.
GET /user/favorites: Requires user_id and language. Returns the list of favorites.
GET /user/favorites/find: Requires user_id. Checks if a certain movie is in the user's favorites