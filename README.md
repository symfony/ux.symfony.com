# ux.symfony.com

Source code for [ux.symfony.com](https://ux.symfony.com).

## Installation

### Forking the repository

To contribute to the website, you need to [fork the **symfony/ux.symfony.com** repository](https://github.com/symfony/ux.symfony.com/fork) on GitHub.
This will give you a copy of the code under your GitHub user account. Read [the documentation "How to fork a repository"](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/working-with-forks/fork-a-repo).

After forking the repository, you can clone it to your local machine:

```shell
# Using GitHub CLI https://cli.github.com/
$ gh repo clone <USERNAME>/ux.symfony.com ux.symfony.com

# Using SSH
$ git clone git@github.com:<USERNAME>/ux.symfony.com.git ux.symfony.com
$ cd ux.symfony.com
# Add the upstream repository, to keep your fork up-to-date
$ git remote add upstream git@github.com:symfony/ux.symfony.com.git
```

### Setting up the development environment

To set up the development environment, you need the following tools:

- **[PHP](https://www.php.net/downloads.php) 8.5 or higher** - Required for running the Symfony application
- **[Composer](https://getcomposer.org/download/)** - PHP dependency manager
- **[Symfony CLI](https://symfony.com/download)** - Recommended for running the development server
- **[Docker](https://www.docker.com/)** - For running services (database, etc.)

With these tools installed, you can install the project dependencies:

```shell
# Install PHP dependencies
symfony composer install
```

### Running the website locally

To run the website in local development:

```shell
symfony serve --open
# The website will be accessible at https://127.0.0.1:9044/
```

### Database

Run database migrations:
```bash
symfony console doctrine:migration:migrate
```

Populate the database:
```bash
symfony console app:load-data
```

### Assets

Download the importmap packages locally:
```bash
symfony console importmap:install
```

Build Toolkit assets:
```bash
composer tailwind:build
```

## Testing

```bash
symfony php bin/phpunit
```
