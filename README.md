# EngineBlock-Theme

## Theme Management

The OpenConext wiki has info regarding the development of themes for OpenConext:
https://github.com/OpenConext/OpenConext-engineblock/wiki/Development-Guidelines#theme-development

### Building
Ensure you have nodejs and ruby installed
```
cd EngineBlock-Theme
gem install compass
npm install
node_modules/.bin/grunt
```

Compiled theme is under the `target/` folder

## Repo Management
This repo is setup in a way that still makes it possible to track upstream
changes from OpenConext-Engineblock.
### Branches:
- `master` (All theme modifications are done here)
- `upstream-theme` (upstream SURFnet theme [git subtree split] from OpenConext-Engineblock/theme directory)
- `upstream-master` (upstream SURFnet OpenConext-Engineblock repo)



## Receiving upstream commits
Update the `upstream-master` branch:
```bash
git remote add upstream https://github.com/OpenConext/OpenConext-Engineblock
git checkout upstream-master
git pull upstream master
```

Update the `upstream-theme` branch:
```bash
git subtree split --prefix=theme --onto upstream-theme -b upstream-theme
```

Update `master` branch with newest theme changes
```bash
git checkout master
git rebase upstream-theme
```
More about how this repo is setup can be found here https://stackoverflow.com/questions/24577084/forking-a-sub-directory-of-a-repository-on-github-and-making-it-part-of-my-own-r
