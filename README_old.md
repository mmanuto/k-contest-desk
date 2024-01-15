<p align="center"><img src="aem.png" alt="Logo" width="150" height="auto"></p>
<h1 align="center"><b>Analisi & Marketing</b></h1>
<p>Questo è il repository di base per il progetto <b>Analisi & Marketing</b>. Qui sono contenute tutte le istruzioni su come sviluppare, gestire e rilasciare il progetto nei vari ambienti.</p>
<br/>

## Prerequisiti
* Docker (https://docs.docker.com/engine/install/ubuntu/)
* Docker Compose (https://docs.docker.com/compose/install/)
* Meta git (https://github.com/mateodelnorte/meta)
* Git flow (https://danielkummer.github.io/git-flow-cheatsheet/index.it_IT.html)

## Inizializzazione
Per iniziare a lavorare basta scaricare questo meta repository. Tramite la libreria meta verranno scaricate anche tutti i repo relativi al progetto.
```
meta git clone git@gitlab.com:analisimarketing/aem.git
```
Verranno scaricati i seguenti repository:
* <b>aemweb</b> (frontend portale web)
* <b>aemgest</b> (frontend gestionale)
* <b>aemserver</b> (backend)
* <b>report-bot</b> (microservizio per salvare pdf su ftp)
* <b>scheduler</b> (microservizio per gestire eventi schedulati e automatici)

## Sviluppo
### Git
Per sviluppare è importante prima spostarsi sul branch `develop`, si può fare velocemente su tutti i repo attraverso il comando:
```
meta git checkout develop
```
### Makefile
Nel repository è presente un `Makefile` contente una serie di comandi utili per lo sviluppo. È possibile consultare la lista di comandi disponibili attraverso 
```
make help 
```
### Installazione librerie
Prima di iniziare bisogna installare le librerie per il backend attraverso il comando 
```
make install
```
### Avvio
Per avviare un istanza del progetto basta eseguire il comando
```
make dev
```
Con questo comando verrà avviato un docker-compose contenente un istanza di `aemweb`, `aemgest` e `aemserver` accessibili in queste porte:
* aemweb (http://localhost:4300)
* aemgest (http://localhost:4200)
* aemserver (http://localhost)

Viene inizializzata anche un istanza di database `mysql`

### Database
Per ora il database viene creato vuoto, va importato da un altra istanza manualmente.

### Debug
È possibile debuggare il backend installando la libreria [php debug](https://marketplace.visualstudio.com/items?itemName=felixfbecker.php-debug) e configurando il debugger in vscode in questo modo, sostituendo `path_to_your_aemserver_folder` con il path della cartella aemserver.
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for XDebug",
            "type": "php",
            "request": "launch",
            "port": 9000,
            "pathMappings": {
                "/code": "/path_to_your_aemserver_folder"
            },
            "xdebugSettings": {
                "max_data": 65535,
                "show_hidden": 1,
                "max_children": 100,
                "max_depth": 5
            }
        }
    ]
}
```

<br/>

## Produzione
Tutto quello che serve per la messa in produzione si trova nella cartella `environment`. È necessario solo creare il file `.env` seguendo la traccia di `.env.sample` e lanciare 
```
docker-compose up -d
```

Tutti i componenti si raggiungono attraverso un nginx proxy di frontiera attraverso i path `/web`, `/gest`, `/backend`. Se la macchina dove viene installato dispone già di un ssl terminator basterà reindirizzare tutto il traffico verso il container `nginx-proxy` che si occuperà poi di gestire tutti il resto.

Esempio configurazione nginx
```
location ~ ^/aem/(.*) {
        rewrite /aem(.*) /$1 break;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_redirect off;
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
}
```

## Notificatore
L'invio delle mail viene fatto tramite notificatore, per avviare un istanza di notificatore basta lanciare il docker-compose dentro la cartella `/notifier` dopo aver configurato il file `.env`.

