## Setup Wip Service Docker environment


### Pipeline API
1 - Pull latest changes from master branch for the Pipeline API repository.

2 - Configure evnironment variables in `path-to-pipeline-api/config/envs-pipeline-api.sh` and source it into your shell:
```
source path-to-pipeline-api/config/envs-pipeline-api.sh
```
3 - Start the API:
```
bundle exec guard
```

### WIP Service
1 - Pull latest changes from master branch for the Wip Service repository.

2 - Create docker machine if not already created.
```
docker-machine create --driver virtualbox dev
```
3 - Start the docker machine.
```
docker-machine start dev
```
4 - Configure evnironment variables in `path-to-wip-service/dev-docker/envs-wip.sh` and source it into your shell:
```
source path-to-wip-service/dev-docker/envs-wip.sh
```
More details about those environment varibles are available in this [Configuration](README.md#configuration) section.

5 - Build the stack docker images.
```
docker-compose -f dev-docker/docker-compose.yml build
```
6 - Start the stack servers and setup wip-service.
```
docker-compose -f dev-docker/docker-compose.yml up
```
7 - Setup ssh authorization
```
(docker exec -it dev-docker_web cat /home/dev/.ssh/id_rsa.pub) >> ~/.ssh/authorized_keys
```
8 - Connect to wip-server
```
docker exec -it dev-docker_web /bin/bash
```

### pipelies CLI
1 - Configure Pipeline Cli credentials.
```
 pipeline_user: 'e7fe97fa-a0c8-4a42-ab8e-2c26d52df059'
 pipeline_pass: 'secret'
 pipeline_endpoint: 'http://127.0.0.1:8080'

 buildsteps_user: 9c2372db-0a09-4190-b86a-aab5f18fc6f7
 buildsteps_pass: Zf6o1kvbNbGkhDI
 buildsteps_endpoint: 'http://192.168.99.100:8081'
```
2 - Start job.
```
pipelines start --application-id=d6a43c82-cc6e-4426-b6eb-883cbe4a99ea --source-vcs-uri=the-github-repo-uri --source-key-path=the-path-to-ssh-key --vcs-path=the-branch-name
```

