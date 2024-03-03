### Introduction
This little script can be used to quickly see what will be included when you turn your project into a Composer package and release a new version.
Only in PHP, as I only work in that, but it's so small that you're free to replicate it.

### History
This was designed when making [Faker](https://github.com/Rockylars/Faker) an open source package, as I didn't see anyone make this before.
It's simple and quite handy, just tells you what will go in, though only cares about top level searches at the moment.

### Usage
Simply call this from your a test you put down in your Unit tests folder, or just the tests folder if you only got that.

### Future
I plan to upgrade this to eventually do all the magical and recursive depth searches, but that's usually far out of scope for my projects anyways.

### Examples
```php
self::assertSame(
    [
        'LICENSE',
        'README.md',
        'composer.json',
        'src'
    ],
    PackageParser::simplePackageSearch(__DIR__ . 'path to the root')
);
```

### Set up the project for commits on Linux
1. Have Docker functional, you don't need an account for this.
2. Have a GitHub account (obviously) for commits.
3. Get an SSH token set up (preferably id_ed25519) and hooked up to your GitHub account.
    - If not, you won't be able to pull/push anything properly.
4. Get the project downloaded and `cd` into the folder.
    - If you plan to make any PR's and don't have rights, make a fork first, grab that, and then attempt to merge PR's of that in.
5. Make sure that running `git config --global --list` and `git config --list` both show `user.email=YOUR_GITHUB_EMAIL`
   and `user.name=YOUR_GITHUB_USER_NAME`.
    - If not, here's the steps to fix it:
    - Set the value for the project and unset the one for local, otherwise set it for local only.
    - Your commits won't link to an account if this is not done.
6. Make sure that running `groups` shows `docker` in it.
    - If not, here's the steps to fix it:
    - run `sudo usermod -aG docker $USER` and then reboot your PC.
    - You won't be able to run the needed Docker commands if this is not done.
7. Make sure that running `ls -la ~/.composer` shows your user instead of `root` for `.`.
    - If not, here's the steps to fix it:
    - Run `sudo chown -R $USER:$USER ~/.composer`.
    - You won't be able to store library authentication and Composer cache if this is not done.
8. Have the `make` extension installed.
9. Run `make setup` and you're done.

[Optional] Get access to private repositories you have access to on GitHub:

10. Generate an access token in GitHub with just the Repo permissions.
11. Run `make composer` and add `config --global github-oauth.github.com YOUR_GENERATED_TOKEN`.