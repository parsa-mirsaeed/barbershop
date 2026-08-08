.PHONY: install reset up down logs test release deploy screenshot mobile mobile-local mobile-status iran-commerce hosting-release hosting-test hosting-audit
install:
	./tools/install.sh
reset:
	./tools/install.sh --reset
up:
	docker compose up -d
down:
	docker compose down
logs:
	docker compose logs -f wordpress
mobile:
	bash tools/mobile-test.sh
mobile-local:
	bash tools/mobile-test.sh --local
mobile-status:
	bash tools/mobile-test.sh --status
iran-commerce:
	bash tools/install-iran-commerce.sh
test:
	python3 tools/scan-secrets.py
	python3 tools/validate.py
	php tests/test_backend.php
	php tests/test_hosting_guard.php
	python3 -m unittest discover -s tests -v
	find theme plugin hosting -name '*.php' -print0 | xargs -0 -n1 php -l
	node --check theme/persian-barbershop/assets/js/site.js
	node --check plugin/barbershop-core/assets/admin.js
	node --check plugin/barbershop-core/assets/frontend.js
	node --check plugin/barbershop-core/assets/ux-refinements.js
	bash -n tools/install.sh tools/deploy.sh tools/build-release.sh tools/mobile-test.sh tools/install-iran-commerce.sh tools/build-hosting-release.sh tools/install-shared-hosting.sh tools/shared-hosting-audit.sh
release:
	./tools/build-release.sh
deploy:
	./tools/deploy.sh
hosting-release:
	bash tools/build-hosting-release.sh
hosting-test:
	php tests/test_hosting_guard.php
	python3 tests/test_shared_hosting.py
	bash tools/build-hosting-release.sh
	python3 tests/test_shared_hosting.py
hosting-audit:
	@test -n "$(URL)" || (echo "Usage: make hosting-audit URL=https://shop.example.com" >&2; exit 2)
	bash tools/shared-hosting-audit.sh "$(URL)"
screenshot:
	python3 tools/capture-theme-screenshot.py
