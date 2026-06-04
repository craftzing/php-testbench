Contributing
===

Contributions are welcome. If you want to ask or propose something, please 
[create an issue](https://github.com/craftzing/php-testbench/issues/new). If you want to contribute, please 
send in a pull request.

## ⤴️ Pull requests

Make sure to follow these rules when creating a pull request:
- Follow the [PSR-12](http://www.php-fig.org/psr/psr-12/) coding standards (though we have a PHP CS Fixer workflow in place that takes care of that for you)
- Write tests for new functionality or bug fixes and make sure test coverage is on point
- Keep the [README](README.md) file and [docs](docs) up-to-date with changes
- We follow [Semantic Versioning](http://semver.org/), so please send pull requests to the correct branch
- Update the [CHANGELOG.md](CHANGELOG.md) file with any changes/additions/... and follow the [changelog standards](http://keepachangelog.com/)

# 🏃‍➡️ Running locally

This project is fully Dockerized, meaning [Docker](https://docs.docker.com) (or [Orbstack](https://orbstack.dev) for macOS users) is the only requirement
to run this project locally. Using Docker Compose, we set up a container for each supported PHP version.

> [!TIP]
> While you can run Docker Compose commands directly, we highly recommend to use our predefined tasks using
> [Task](https://taskfile.dev). All docs will always refer to these tasks, but if you prefer not to install
> Task, you can inspect [Taskfile.yml](./Taskfile.yml) to see which Docker Compose commands are used under the hood.

To explore all available tasks, run:
```shell
task
```
