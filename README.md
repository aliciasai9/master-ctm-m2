# Projet M2 CTM — Patrimoine UNESCO

Ce projet contient :
- `sql/corpus_unesco.sql` : base MySQL complete (creation + donnees)
- `index.php` : page PHP dynamique (MySQLi + foreach + schema.org)
- `reporting.md` : reporting methodologique

---

## Demarrage ultra simple (Mac + Windows)

Si vous n'avez **ni PHP ni MySQL**, prenez la voie la plus facile :

### Option recommandee : XAMPP (debutant)

1. Installer XAMPP : [https://www.apachefriends.org/fr/index.html](https://www.apachefriends.org/fr/index.html)
2. Ouvrir XAMPP Control Panel.
3. Demarrer **Apache** et **MySQL**.
4. Cloner ou telecharger ce repo.
5. Mettre le dossier du projet dans :
   - **Mac** : `/Applications/XAMPP/xamppfiles/htdocs/`
   - **Windows** : `C:\xampp\htdocs\`
6. Importer la base :
   - Ouvrir `http://localhost/phpmyadmin`
   - Onglet **Importer**
   - Choisir `sql/corpus_unesco.sql`
   - Cliquer **Executer**
7. Ouvrir la page :
   - `http://localhost/master-ctm-m2/index.php`
   - (ou le nom reel de votre dossier)

> Cette methode evite les problemes de commandes terminal.

---

## Option terminal (si vous preferez les commandes)

## 1) Recuperer le projet

```bash
git clone https://github.com/aliciasai9/master-ctm-m2.git
cd master-ctm-m2
```

## 2) Installer ce qui manque

### Mac (Homebrew)

```bash
brew install mysql php
brew services start mysql
```

### Windows

Le plus simple est d'utiliser XAMPP (voir section au-dessus).  
Si vous voulez vraiment passer par terminal, installez PHP et MySQL puis ajoutez-les au `PATH`.

## 3) Importer la base SQL

### Cas A: root sans mot de passe (frequent en local)

```bash
mysql -u root < sql/corpus_unesco.sql
```

### Cas B: root avec mot de passe

```bash
mysql -u root -p < sql/corpus_unesco.sql
```

## 4) Lancer la page PHP

```bash
php -S 127.0.0.1:8000
```

Puis ouvrir :
- `http://127.0.0.1:8000/index.php`

---

## Si vous avez une erreur de connexion SQL

Editez le haut du fichier `index.php` :

- host : `127.0.0.1`
- user : `root`
- password : ``
- database : `corpus_ctm`

Adaptez selon votre machine (ex: mot de passe root non vide).

---

## Verification finale (checklist)

- La base `corpus_ctm` existe
- La table `sites_unesco` existe
- La page s'ouvre sans erreur
- Les cartes UNESCO s'affichent
- Le filtre `pays` fonctionne
- Les microdonnees schema.org sont presentes

Commande de controle SQL :

```bash
mysql -u root -p -e "USE corpus_ctm; SHOW TABLES; SELECT COUNT(*) AS total FROM sites_unesco;"
```
