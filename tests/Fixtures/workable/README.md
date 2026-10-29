# Workable fixtures

Representative stand-ins matching the Workable API shapes (design §2). They are not a live capture.

Replace them with a live capture once the subdomain and token arrive:

```
curl -H "Authorization:Bearer <token>" "https://<subdomain>.workable.com/spi/v3/jobs?state=published&include_fields=description,full_description,requirements,benefits"
```

Also capture `GET /spi/v3/jobs/{shortcode}/application_form`.

The real payload wins over these files. No real candidate PII is stored here.
