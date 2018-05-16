# Rhoban patches tagger

## Introduction

The tagger is a tool allowing you to upload several patches (small images) and tag them under some categories in order to train neural networks

![tagger.png](tagger.png)

## Installing

### Cloning and getting dependencies

The current version is based on the Symfony framework and require a MySQL database.

First, clone this repository and run the [composer installation](https://getcomposer.org/):

    composer install

*Note: composer can be obtained on [getcomposer.org](https://getcomposer.org/) or via `apt install composer` on a debian installation*

### Configuring

You then need to configure the app, edit the `.env` file and change the `DATABASE_URL` so that it matches your database configuration.

### Getting the first administrator

You can now deploy and run the application (reaching the Symfony `public/` directory from your web server supporting PHP), and register your first account.

To promote the first admin, you can use the Friends Of Symfony command line:

    ./bin/console fos:user:promote

And then enter the username, and `ROLE_ADMIN` like this:

    Please choose a username: gregwar
    Please choose a role: ROLE_ADMIN

You might need to logout and login again to have this taking effect.
