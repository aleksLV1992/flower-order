# Импорт заказов маркетплейса (тестовое, Senior Fullstack)

```bash
docker compose up -d --build   # UI: http://localhost:8080
make test        # QA (CS Fixer, PHPStan max, Rector, Deptrac) + PHPUnit + Vitest + e2e smoke — всё в Docker
```

Всё остальное — решения, допущения, что не сделано и почему — в [DECISIONS.md](DECISIONS.md).
