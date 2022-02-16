<p align="center">
    <a href="mailto: maulik.shah1910@gmail.com" target="_blank">
        <h1>Aspire Code Challenge<h1>
<!--         <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"> -->
    </a>
</p>
        
## About Code Challenge
This challenge is given by <a href="https://aspireapp.com/" target="_blank">Aspire</a> as a part of candidate selection process.<br />
The challenge states to build a mini-aspire API.</p>
Its specification is: <br /> 
It is an app that allows authenticated users to go through a loan application. It doesn’t have to contain too many fields, but at least “amount
required” and “loan term.” All the loans will be assumed to have a “weekly” repayment frequency.<br />
After the loan is approved, the user must be able to submit the weekly loan repayments. It can be a simplified repay functionality, which won’t
need to check if the dates are correct but will just set the weekly amount to be repaid.
        
Application is built using <a href="https://laravel.com/" target="_blank">Laravel</a>
    

## Installation Steps
After checkout, several commands needs to be executed to setup this project.
    
    composer install
After installation, we need to get and setup .env file using command:
    
    sudo cp .env.example .env
Once we have .env file, we have to setup application key using command:
    
    php artisan key:generate
Now, you have to create a database blank database and configure database into our .env file over this commands:
Here, you have to provide your details for `database`, `username`, `password`.
    
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=<your db-name>
    DB_USERNAME=<mysql user name>
    DB_PASSWORD=<mysql password>

Since we have integrated JWT for generation of authentication token over APIs, we have to publish JWT vendor and generate a JWT key in our .env file.
    
    php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
    php artisan jwt:secret

Once all these setup is done, we shall autoload all resources and optimize the project:
    
    composer dump-autoload
    php artiasn optimize:clear
    
Now that we have setup ready, we can proceed for databaes migrations.
    
    php artisan migrate
    
After migration is done, we are all set to run our project by command:
    
    php artisan serve --port=<your_custom_port> --host=<custom_host>
Here, `--port` and `--host` are optional parameters. If we do not provide host, it taken `localhost` or `127.0.0.1` by default. And default port is `8000` (if it is free)
    
## Further documentation for functionalities:
For functional specifications and their parameters and responses, please refer to associated controllers in `app/Http/Controller/API/` location.
All routes related to this mini-aspire API are located in `routes/api.php` file.
