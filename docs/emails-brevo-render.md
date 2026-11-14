# E-mails transactionnels : Mailpit et Brevo HTTPS

## Configuration

Le projet Symfony 7.4 utilise `symfony/brevo-mailer` 7.4, sélectionné par
`MAILER_DSN`. `symfony/http-client` 7.4 est déjà une dépendance de production
et son service `http_client` est injecté dans le transport Brevo.

En local, les valeurs publiques de `.env` restent fictives et utilisent Mailpit :

```dotenv
MAILER_DSN=smtp://127.0.0.1:1025
MAILER_FROM_EMAIL=bonjour@vite-et-gourmand.example
```

Démarrer Mailpit avant de tester les vrais parcours en local. `.env.local` peut
conserver les réglages propres au poste ; il est ignoré par Git et Docker.

Sur Render, définir les variables d’environnement du service web :

| Variable | Valeur |
| --- | --- |
| `APP_ENV` | `prod` |
| `APP_DEBUG` | `0` |
| `MAILER_DSN` | `brevo+api://VOTRE_CLE_API_BREVO@default` |
| `MAILER_FROM_EMAIL` | Adresse d’expéditeur validée dans le compte Brevo |
| `DEFAULT_URI` | URL publique HTTPS du site pour les liens générés hors requête HTTP |

La clé est une clé **API Brevo**. Si elle contient des caractères réservés dans
une URI, encoder ces caractères avant de composer le DSN. Saisir les vraies valeurs
dans les paramètres Render, jamais dans un fichier versionné. La valeur fictive
locale de `MAILER_FROM_EMAIL` doit être remplacée sur Render.

Le transport appelle `https://api.brevo.com/v3/smtp/email` par POST. La bibliothèque
gère le format JSON et l’en-tête d’authentification à partir du DSN.
La validation de l’expéditeur et l’authentification de son domaine se font dans
Brevo avant l’envoi en production.

Le Dockerfile installe déjà les dépendances Composer de production. Le prochain
build de l’image embarquera le client HTTP et le bridge Brevo depuis le lock mis à jour.

## Expéditeurs et mode d’envoi

L’adresse From est injectée depuis `MAILER_FROM_EMAIL` dans les quatre services
`EmailBienvenue`, `EmailConfirmationCommande`, `EmailInvitationAvis` et
`EmailRetourMateriel`, ainsi que dans `ResetPasswordController` pour le reset.
Le nom d’expéditeur reste **Vite & Gourmand** dans les cinq cas.

Messenger n’est pas installé dans les dépendances du projet. Aucune configuration
de transport Messenger, de routage de `SendEmailMessage` ou de worker Render n’est
présente dans le dépôt. `config/packages/mailer.yaml` impose en plus
`framework.mailer.message_bus: false` sous `when@prod` : l’envoi est effectué
directement par le transport, pendant la requête. Aucun worker n’est requis,
même si Messenger est ajouté ultérieurement pour un autre usage.

Les comportements existants de gestion des erreurs d’envoi sont conservés. Cette
configuration n’ajoute pas de file persistante ni de mécanisme de réessai.

## Vérifications

```powershell
php tests/emails_transactionnels.php
php bin/console lint:yaml config
php bin/console lint:container
php bin/console lint:container --env=prod --no-debug
php bin/console debug:config framework mailer --env=prod --no-debug
php bin/console debug:container http_client --env=prod --no-debug
composer validate --strict --no-check-publish
git diff --check
```

Si le PHP CLI du poste charge l’extension MongoDB ponctuellement, utiliser le
préfixe PHP suivant pour ces commandes :

```powershell
$mongoExtension = (Get-ChildItem var/tools/mongodb-2.5.7 -Filter '*.dll').FullName
php -d "extension=$mongoExtension" tests/emails_transactionnels.php
```

Le test automatise les quatre services et le vrai parcours HTTP de demande de
réinitialisation avec des comptes dans SQLite temporaire. Il vérifie les cinq From
avec une adresse distincte de l’exemple local, puis simule une réponse Brevo 201 et
contrôle le POST HTTPS, l’authentification et le contenu expéditeur/destinataire.
Il vérifie aussi que le DSN Mailpit sélectionne toujours le transport SMTP.
Aucun message n’est envoyé sur Internet ou à Mailpit par ce test.

Après configuration et déploiement sur Render, il reste à réaliser un envoi réel
vers une boîte de test autorisée, puis à vérifier la réception, l’expéditeur et les
liens de réinitialisation/avis. Aucun envoi Brevo réel n’a été effectué lors de la
préparation du code.

Références : [Symfony Mailer 7.4](https://symfony.com/doc/7.4/mailer.html),
[API transactionnelle Brevo](https://developers.brevo.com/docs/send-a-transactional-email),
[expéditeurs et domaines Brevo](https://developers.brevo.com/docs/getting-started-with-senders-and-domains).
