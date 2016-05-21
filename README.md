# <example-project>

> **Note, here's a [diff of commits availabe upstream](https://github.com/generoi/drupal-project/compare/genero...drupal-composer:8.x)**

This project template should provide a kickstart for managing your site
dependencies with [Composer](https://getcomposer.org/).

If you want to know how to use it as replacement for
[Drush Make](https://github.com/drush-ops/drush/blob/master/docs/make.md) visit
the [Documentation on drupal.org](https://www.drupal.org/node/2471553).

## Installation

### Local development

    git clone --recursive git@github.com:generoi/<example-project>.git <example-project>
    cd <example-project>

    # Setup git hooks
    ./lib/git-hooks/install.sh

    # Install dependencies
    bundle
    composer install

    # Setup the ENV variables (pre-configured for the VM)
    cp .env.example .env

    # Build the VM
    vagrant up --provision

    # To sync files from your computer to the virtual machine, run
    vagrant rsync-auto

    # Install theme dependencies
    # If npm install fails, make sure you have the lastest node and npm installed
    cd web/sites/themes/custom/example
    npm install
    bower install

## Setup a new repository

1. Clone the repo - `git clone --recursive git@github.com:generoi/drupal-project.git foobar`
2. Setup git hooks `./lib/git-hooks/install.sh`
3. Install dependencies `bundle; composer install`
4. Rename everything (relies on your theme being named the same as the repository)

    ```sh
    # Search and replace all references to the project
    find . -type f -print0 | xargs -0 sed -i 's/<example-project>/foobar/g'

    # You need to manually setup the remote environment hosts in:
    # - `config/environments.yml`
    ```
5. Add a unique hash salt in the `.env.example`.
6. Prepare your own ENV variables (pre-configured for the VM) `cp .env.example .env`
7. Configure the correct build tasks, application name, and repo path in `package.json`.
8. Setup the new remote git repository

    ```sh
    # Remove the existing master branch
    git branch -D master

    # Switch to a new master branch for this project
    git checkout -b master

    # Create a new repository on github
    open https://github.com/organizations/generoi/repositories/new

    # Set origin url to to the newly created github repository
    git remote set-url origin git@github.com:generoi/<example-project>.git

    # Push the code
    git push -u origin master
    ```

9. Setup the VM

    ```sh
    # Change the VM IP to something unique
    vim config/local.config.yml

    # Build the VM
    vagrant up --provision

    # To sync files from your computer to the virtual machine, run
    vagrant rsync-auto
    ```

## Usage

First you need to [install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).

> Note: The instructions below refer to the [global composer installation](https://getcomposer.org/doc/00-intro.md#globally).
You might need to replace `composer` with `php composer.phar` (or similar) 
for your setup.

After that you can create the project:

```
composer create-project drupal-composer/drupal-project:8.x-dev some-dir --stability dev --no-interaction
```

With `composer require ...` you can download new dependencies to your 
installation.

```
cd some-dir
composer require drupal/devel:8.*
```

The `composer create-project` command passes ownership of all files to the 
project that is created. You should create a new git repository, and commit 
all files not excluded by the .gitignore file.

## What does the template do?

When installing the given `composer.json` some tasks are taken care of:

* Drupal will be installed in the `web`-directory.
* Autoloader is implemented to use the generated composer autoloader in `vendor/autoload.php`,
  instead of the one provided by Drupal (`web/vendor/autoload.php`).
* Modules (packages of type `drupal-module`) will be placed in `web/modules/contrib/`
* Theme (packages of type `drupal-theme`) will be placed in `web/themes/contrib/`
* Profiles (packages of type `drupal-profile`) will be placed in `web/profiles/contrib/`
* Creates default writable versions of `settings.php` and `services.yml`.
* Creates `sites/default/files`-directory.
* Latest version of drush is installed locally for use at `vendor/bin/drush`.
* Latest version of DrupalConsole is installed locally for use at `vendor/bin/drupal`.

## Updating Drupal Core

This project will attempt to keep all of your Drupal Core files up-to-date; the 
project [drupal-composer/drupal-scaffold](https://github.com/drupal-composer/drupal-scaffold) 
is used to ensure that your scaffold files are updated every time drupal/core is 
updated. If you customize any of the "scaffolding" files (commonly .htaccess), 
you may need to merge conflicts if any of your modfied files are updated in a 
new release of Drupal core.

Follow the steps below to update your core files.

1. Run `composer update drupal/core`.
1. Run `git diff` to determine if any of the scaffolding files have changed. 
   Review the files for any changes and restore any customizations to 
  `.htaccess` or `robots.txt`.
1. Commit everything all together in a single commit, so `web` will remain in
   sync with the `core` when checking out branches or running `git bisect`.
1. In the event that there are non-trivial conflicts in step 2, you may wish 
   to perform these steps on a branch, and use `git merge` to combine the 
   updated core files with your customized files. This facilitates the use 
   of a [three-way merge tool such as kdiff3](http://www.gitshah.com/2010/12/how-to-setup-kdiff-as-diff-tool-for-git.html). This setup is not necessary if your changes are simple; 
   keeping all of your modifications at the beginning or end of the file is a 
   good strategy to keep merges easy.

## Generate composer.json from existing project

With using [the "Composer Generate" drush extension](https://www.drupal.org/project/composer_generate)
you can now generate a basic `composer.json` file from an existing project. Note
that the generated `composer.json` might differ from this project's file.


## FAQ

### Should I commit the contrib modules I download

Composer recommends **no**. They provide [argumentation against but also 
workrounds if a project decides to do it anyway](https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md).

### How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull 
request is often a better solution), you can do so with the 
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra 
section of composer.json:
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL to patch"
        }
    }
}
```
