# factor-app
# Factor App

Ce projet est une API permettant la gestion des fournisseurs et des services. Il a été développé en utilisant Symfony et Doctrine, et utilise Docker pour la conteneurisation.

## Prérequis

- PHP >= 8.1
- Composer
- Docker et Docker Compose

## Installation

### Étapes d'installation

1. Clonez le dépôt :
   ```bash
   git clone https://github.com/SatiNotaDev/factor-app.git
   ```

2. Accédez au répertoire du projet :
   ```bash
   cd factor-app
   ```

3. Installez les dépendances avec Composer :
   ```bash
   composer install
   ```

4. Configurez les variables d'environnement :
   ```bash
   cp .env.example .env
   ```
   Modifiez le fichier `.env` avec vos paramètres de configuration (par exemple, `DATABASE_URL`, `MAILER_DSN`, etc.).

### Lancement avec Docker

Le projet supporte Docker. Pour le lancer, exécutez :

```bash
docker-compose up -d
```

Cela va démarrer les services d'application, de base de données et de Redis en conteneurs.

## Lancement de l'application

Pour lancer l'application en mode de développement sans Docker, utilisez la commande suivante :

```bash
php bin/console server:run
```

Accédez ensuite à l'URL `http://localhost:8000` pour voir l'application en action.

## Exécution des tests

Pour lancer les tests unitaires, utilisez la commande suivante :

```bash
./vendor/bin/phpunit
```

Assurez-vous que votre environnement de test est correctement configuré et que les dépendances sont installées.

## Commandes Administratives

- **Nettoyage du cache** :
  ```bash
  php bin/console app:clear-cache
  ```
- **Migration de la base de données** :
  ```bash
  php bin/console doctrine:migrations:migrate
  ```

## Structure des branches Git

- **main** : Branch principale avec la version stable du projet.
- **dev** : Branch pour le développement actif.
- **test** : Branch pour les tests avant de fusionner dans `main`.
- **feature/update** : Branch pour les nouvelles fonctionnalités ou les mises à jour.

## Déploiement

Le projet utilise GitHub Actions pour automatiser les tests et la construction de l'application. Pour déployer manuellement l'application, vous pouvez utiliser un script de déploiement via SSH ou tout autre outil approprié.

## Contribution

Les contributions sont les bienvenues ! Merci de suivre les standards de codage et de faire des tests avant de soumettre une pull request.

## License

Ce projet est sous licence MIT. Pour plus de détails, consultez le fichier LICENSE.

