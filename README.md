# itk-os2display developer admin App

This is a symfony project that contains the bundles we work on at ITK.

Run

```sh
scripts/install_bundles.sh
```

to set up repositories in `composer.json`.

Use

```sh
scripts/install_bundles.sh --dev
```

to clone bundles to local storage (`../bundles`) and symlink from `vendor`.

To install assets, run

```sh
app/console assets:install --symlink
```


# Fixtures


Before loading fixtures we have to make sure that a search index is active.

First make sure that the `search_node` service is running:

```
sudo service search_node stop; sudo service search_node start
```

Then we can activate the sample index like this:

```
sudo service search_node stop; sudo service search_node start
token=$(curl --silent --insecure --header 'Content-type: application/json' --data '{ "apikey": "795359dd2c81fa41af67faa2f9adbd32" }' https://search.os2display.vm/authenticate/ | php -r 'echo json_decode(stream_get_contents(STDIN))->token;')
curl --silent --insecure --header "Authorization: Bearer $token" https://search.os2display.vm/api/e7df7cd2ca07f4f1ab415d457a6e1c13/activate
```

Now we can load fixtures:

```
app/console doctrine:migrations:migrate --quiet --no-interaction first \
	&& app/console doctrine:migrations:migrate --quiet --no-interaction \
	&& app/console doctrine:fixtures:load --no-interaction
```

Finally flush the index and re-index content:

```
token=$(curl --silent --insecure --header 'Content-type: application/json' --data '{ "apikey": "795359dd2c81fa41af67faa2f9adbd32" }' https://search.os2display.vm/authenticate/ | php -r 'echo json_decode(stream_get_contents(STDIN))->token;')
curl --silent --insecure --header "Authorization: Bearer $token" --request DELETE https://search.os2display.vm/api/e7df7cd2ca07f4f1ab415d457a6e1c13/flush

app/console os2display:core:reindex
```
