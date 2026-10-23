#!/usr/bin/env bash
set -euo pipefail
BASE="${1:-http://localhost:8080}"
FIXTURE="$(dirname "$0")/../frontend/public/marketplace-orders.json"
fail() { echo "FAIL: $*" >&2; exit 1; }
summary() { python3 -c 'import json,sys; s=json.load(sys.stdin)["summary"]; print(" ".join(f"{k}={s[k]}" for k in ("total","created","updated","unchanged","duplicate","rejected")))'; }
count() { docker compose exec -T db mysql -uapp -papp app -N -e "$1" 2>/dev/null; }

docker compose exec -T db mysql -uapp -papp app -e "DELETE FROM order_item; DELETE FROM orders;" 2>/dev/null

echo "1. fixture is served by the frontend"
curl -fsS "$BASE/marketplace-orders.json" | cmp -s - "$FIXTURE" || fail "served fixture differs from repo file"
cmp -s "$FIXTURE" "$(dirname "$0")/../backend/tests/Fixtures/marketplace-orders.json" || fail "backend test fixture is out of sync with frontend/public"

echo "2. first import"
got=$(curl -fsS -X POST "$BASE/api/shops/1/imports" -H 'Content-Type: application/json' --data-binary @"$FIXTURE" | summary)
[ "$got" = "total=11 created=7 updated=0 unchanged=0 duplicate=1 rejected=3" ] || fail "first import: $got"

echo "3. repeated import changes nothing"
got=$(curl -fsS -X POST "$BASE/api/shops/1/imports" -H 'Content-Type: application/json' --data-binary @"$FIXTURE" | summary)
[ "$got" = "total=11 created=0 updated=0 unchanged=7 duplicate=1 rejected=3" ] || fail "re-import: $got"

echo "4. 10 concurrent imports on a clean DB"
docker compose exec -T db mysql -uapp -papp app -e "DELETE FROM order_item; DELETE FROM orders;" 2>/dev/null
tmp=$(mktemp -d)
for i in $(seq 1 10); do
  curl -sS -o "$tmp/$i.json" -w "%{http_code}\n" -X POST "$BASE/api/shops/1/imports" -H 'Content-Type: application/json' --data-binary @"$FIXTURE" > "$tmp/$i.code" &
done
wait
for i in $(seq 1 10); do [ "$(cat "$tmp/$i.code")" = "200" ] || fail "concurrent request $i: HTTP $(cat "$tmp/$i.code")"; done
created=$(cat "$tmp"/*.json | python3 -c 'import json,sys; print(sum(json.loads(l)["summary"]["created"] for l in sys.stdin.read().replace("}{","}\n{").splitlines()))')
[ "$created" = "7" ] || fail "concurrent: created total $created, expected 7"
[ "$(count 'SELECT COUNT(*) FROM orders')" = "7" ] || fail "orders in DB != 7"
[ "$(count 'SELECT COUNT(*) FROM order_item')" = "7" ] || fail "items in DB != 7"

echo "5. list, filter, pagination"
curl -fsS "$BASE/api/shops/1/orders?status=new" | python3 -c '
import json,sys; d=json.load(sys.stdin)
assert [o["externalId"] for o in d["items"]] == ["MP-1009","MP-1004","MP-1003","MP-1001"], d["items"]
assert d["statusCounts"] == {"new":4,"accepted":1,"delivering":0,"delivered":1,"cancelled":1}, d["statusCounts"]
o = d["items"][0]; assert (o["itemsTotal"], o["delivery"]["cost"], o["grandTotal"], o["needsReview"]) == (360000, 70000, 430000, True), o
' || fail "list"
[ "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/api/shops/1/orders?status=bogus")" = "400" ] || fail "bad filter must be 400"

echo "6. status update on re-import, final status is kept"
sed 's/"status": "NEW"/"status": "ACCEPTED"/; s/"status": "DONE"/"status": "NEW"/' "$FIXTURE" > "$tmp/changed.json"
curl -fsS -X POST "$BASE/api/shops/1/imports" -H 'Content-Type: application/json' --data-binary @"$tmp/changed.json" > "$tmp/changed-report.json"
[ "$(count "SELECT status FROM orders WHERE external_id='MP-1001'")" = "accepted" ] || fail "MP-1001 not updated"
[ "$(count "SELECT status FROM orders WHERE external_id='MP-1005'")" = "delivered" ] || fail "MP-1005 rolled back from final"

rm -rf "$tmp"
echo "OK: all smoke checks passed"
