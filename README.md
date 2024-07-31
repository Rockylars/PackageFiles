### Introduction
This script can be used to quickly see what will be included when you turn your project into a Composer package and release a new version.

Simply call this from your test you put down in your Unit tests folder, or just the tests folder if you only got that.

### History
This was originally designed when making [Faker](https://github.com/Rockylars/Faker) an open source package, as I didn't see anyone make this before.
It's quite handy, just tells you what will go in.

The 2nd version has taken it from a simple root directory/file matcher to a recursive pattern matcher.

### Examples
```php
self::assertSame(
    [
        'LICENSE',
        'README.md',
        'composer.json',
        'src/'
    ],
    PackageParser::run()
);
```

### Limitations
The `.gitignore` and `.gitattributes` files struggle with file/folder names that start with spaces, end with spaces or are just entirely spaces.
While this thing is also rather fast, you can speed it up by preventing deep search on things like the `vendor` folder.


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

### Functions of advanced searches
- https://git-scm.com/docs/gitignore
- https://opensource.com/article/20/8/dont-ignore-gitignore
- https://www.w3schools.com/git/git_ignore.asp
- https://man7.org/linux/man-pages/man7/glob.7.html
- The `.gitattribute` file can be put on multiple levels and will take that as it's root, they are found automatically and just the same as `.gitignore` files.
- Patterns can not look upwards on the file tree.
- Patterns for the `.gitattributes` `export-ignore` are the same as those for `.gitignore`.
- Lower files can cancel out upper files, so a name match will be cancelled out by a lower not match.
- You can ignore lower `.gitignore` and `.gitattributes` files.

Testing what `.gitattributes` does is a journey, but the easiest way I've found just make a branch and commit your stuff into it, where you can confirm it behaves just like `.gitignore`.
Any uncommitted files will not work with the following command, but you don't have to push this remote.
You use `git archive --format=zip --output=test.zip NAME_OF_TEST_FILE_BRANCH_BASED_ON_CHANGE_BRANCH` to generate it.
You can use `git ls-tree NAME_OF_BRANCH -r --name-only` to see what could be getting committed and `{ git ls-tree -r main --name-only; find SOME_FOLDER_PATH MORE_FOLDER_PATHS -type f; }` to investigate it deeper