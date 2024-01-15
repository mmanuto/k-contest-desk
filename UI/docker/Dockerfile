FROM node:16
WORKDIR /app

COPY package*.json ./

RUN npm install -g @angular/cli

COPY ./docker/docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

EXPOSE 4200

ENTRYPOINT /docker-entrypoint.sh