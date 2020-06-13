### Local Dev
```bash
docker-compose -f stack.yaml up
docker-compose -f stack.yaml up -d
docker-compose -f stack.yaml down
```

### Deploy
```bash
ssh dh_hkr2aa@aquestionadaykeepsthedivorceaway.com "cd daily-couples-journal && git pull  && cd .. && rsync -avz daily-couples-journal/public_html/ aquestionadaykeepsthedivorceaway.com/"
```
