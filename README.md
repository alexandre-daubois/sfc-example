# Make your Symfony app observable

Workshop material for "A Symfony app in production always looks fine… until it doesn't", SymfonyCon Warsaw.

A small shop runs on Symfony behind FrankenPHP in worker mode, with PostgreSQL and a Messenger worker. Every step breaks it in a different way, and you find out why with what the server and the runtime already expose: Ember on the outside, logs, SPX and metrics on the inside.

## Before the workshop

You need Docker, Git, Make and [Ember](https://github.com/alexandre-daubois/ember):

```console
brew install alexandre-daubois/tap/ember
# or
go install github.com/alexandre-daubois/ember/cmd/ember@latest
```

Then, at home, so nothing has to be downloaded on the conference Wi-Fi:

```console
git clone <this repository> symfonycon-workshop
cd symfonycon-workshop
make check
make prepare
```

## During the workshop

```console
make step N=01   # move to a step, your changes go to a stash
make up          # start the shop on http://shop.localhost:8800
make ember       # second terminal
make load        # third terminal
make help        # every command of every step
```

Each step is a Git tag. `step-NN` is where the exercise starts, `step-NN-solution` is where it ends, and `make solution N=NN` shows the difference. Edit the code in `app/`, then `make restart` to load it: in worker mode, PHP keeps your code in memory until the workers restart.

Moving on never needs your own code: every step starts from the solution of the previous one. `make step N=04` puts whatever you changed in a Git stash, so nothing is lost and Git never refuses to switch, then restarts the shop on the start of step 04. `git stash list` shows what you saved.

The ports, all bound to localhost:

| Port | What |
|---|---|
| 8800 | The shop, through FrankenPHP |
| 2019 | The Caddy admin API, read by Ember |
| 6060 | Private port: SPX and metrics, never reachable from the shop |
