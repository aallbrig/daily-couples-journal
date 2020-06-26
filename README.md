## Local Dev
```bash
docker-compose -f stack.yaml up
docker-compose -f stack.yaml up -d
docker-compose -f stack.yaml down
```

## Hacks

### Deploy
```bash
ssh dh_hkr2aa@aquestionadaykeepsthedivorceaway.com "cd daily-couples-journal && git pull  && cd .. && rsync -avz daily-couples-journal/public_html/ aquestionadaykeepsthedivorceaway.com/"
```

### Generate .env.sample
```bash
cat .env | sed -E 's/^(.*=)(.*)/\1/g' > .env.sample
```

### Copy vendor files into public_html
_This assumes `docker-compose -f stack.yaml up` or `docker-compose -f stack.yaml build php` has been run once_
```bash
docker cp $(docker run -d --rm daily-couples-journal_php:latest):/var/www/html/vendor public_html
```

### View all TODO or HACKs that exist in code
```bash
grep -R --exclude-dir=vendor --exclude=README.md 'TODO' .
grep -R --exclude-dir=vendor --exclude=README.md 'HACK' .
```

