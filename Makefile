.DEFAULT_GOAL := help
BACKEND_NEST_ID := $(shell docker ps -qf "name=aembackend")
FRONTEND_GEST_ID := $(shell docker ps -qf "name=aemgest")
FRONTEND_WEB_ID := $(shell docker ps -qf "name=aemweb")
BACKEND_PHP_ID := $(shell docker ps -qf "name=aemserver")
JAPSER_ID := $(shell docker ps -qf "name=jasper_jasperreports_1")

#help:	@ List available tasks on this project
help:
	@grep -E '[a-zA-Z\.\-]+:.*?@ .*$$' $(MAKEFILE_LIST)| sort | tr -d '#'  | awk 'BEGIN {FS = ":.*?@ "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

#install:	@ Install dependencies for all projects
install:
	cd aemserver; sh install.sh

#dev:	@ Start up a local development envinroment
dev:
	docker-compose up -d --build

#stop:	@ Stop current docker compose
stop:
	docker-compose down

#jasper-start:	@ Start Jasper Server
jasper-start:
	docker-compose -f jasper/docker-compose.yml up -d

#jasper-stop:	@ Stop Jasper Server
jasper-stop:
	docker-compose -f jasper/docker-compose.yml down

#code:	@ Open VSCode with aem workspace
code:
	code aem.code-workspace

#build-gest:	@ Build docker image for gest
build-gest:
	docker build -f ./aemgest/docker/Dockerfile.prod -t registry.gitlab.com/analisimarketing/aemgest-desktop ./aemgest/.

#build-web:	@ Build docker image for web
build-web:
	docker build -f ./aemweb/docker/Dockerfile.prod -t registry.gitlab.com/analisimarketing/aemweb-desktop ./aemweb/.

#build-server:	@ Build docker image for server
build-server:
	docker build -f ./aemserver/docker/Dockerfile.prod -t registry.gitlab.com/analisimarketing/aemserver ./aemserver/.

#build-report-bot:	@ Build docker image for report-bot
build-report-bot:
	docker build -f ./report-bot/docker/Dockerfile -t  registry.gitlab.com/analisimarketing/backend-report-bot ./report-bot/.

#build-scheduler:	@ Build docker image for scheduler
build-scheduler:
	docker build -f ./scheduler/docker/Dockerfile -t  registry.gitlab.com/analisimarketing/scheduler ./scheduler/.

#build-backend:	@ Build docker image for backend
build-backend:
	docker build -f ./backend/Dockerfile -t registry.gitlab.com/analisimarketing/backend ./backend/.

#nest-log: @ Backend NestJS logs tail -f
nest-log:
	docker logs --follow $(BACKEND_NEST_ID)

#jasper-log: @ Jasper Server logs tail -f
jasper-log:
	docker logs --follow $(JAPSER_ID)
	
#nest-console: @ Backend NestJS sh console
nest-console:
	docker exec -it $(BACKEND_NEST_ID) sh

#jasper-console: @ Jasper Server sh console
jasper-console:
	docker exec -it $(JAPSER_ID) sh

#php-error: @ Backend PHP error tail -f
php-error:
	tail -f ./aemserver/logs/error.log

#php-debug: @ Backend PHP debug tail -f
php-debug:
	tail -f ./aemserver/logs/debug.log

#php-sh: # Shell on PHP container
php-sh:
	docker exec -w /code -it $(BACKEND_PHP_ID) bash

gest-sh:
	docker exec -w /code -it $(BACKEND_PHP_ID) bash

#gest-log: @ Frontend Gest logs tail -f
gest-log:
	docker logs --follow $(FRONTEND_GEST_ID)

#web-log: @ Frontend Web logs tail -f
web-log:
	docker logs --follow $(FRONTEND_WEB_ID)

#pull: @ meta git pull
pull:
	meta git pull

#status: @ meta git status
status:
	meta git status

#nest-sh: @ docker exec -it <nest-container> sh
nest-sh:
	docker exec -it $(BACKEND_NEST_ID) sh
