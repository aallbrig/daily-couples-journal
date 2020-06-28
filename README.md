# A question a day keeps the divorce away - a texting based couples journal
The [A Question a Day Keeps the Divorce Away](https://aquestionadaykeepsthedivorceaway.com/) is a texting based daily couples journal. The idea is to sign up a pair of people to receive daily text messages to encourage the pair to learn more about each other through the ensuing conversations.

The name of the product is meant to be tounge in cheek - but sharing and learning about each other as a couple _is_ very important!

## Technical Babble
1. The product is implementing using the LAMP stack to easily deploy onto a shared hosting solution.
    - Dreamhost for domain & shared hosting solution
    - SSH for deploy
    - Docker & docker-compose for acceptable local dev experience
    - MySQL DB
    - Vanilla PHP
    - HTML5
    - Bootstrap CSS
    - Vanilla JS
1. Payment API - Stripe API
1. Texting API - Twilio API
1. API testing/dev Postman

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

