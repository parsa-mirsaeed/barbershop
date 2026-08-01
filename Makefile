.PHONY: install reset up down logs test release deploy screenshot mobile mobile-local mobile-status
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
test:
	python3 tools/scan-secrets.py
	python3 tools/validate.py
	php tests/test_backend.php
	python3 -m unittest discover -s tests -v
	find theme plugin -name '*.php' -print0 | xargs -0 -n1 php -l
	node --check theme/persian-barbershop/assets/js/site.js
	node --check plugin/barbershop-core/assets/admin.js
	node --check plugin/barbershop-core/assets/frontend.js
	bash -n tools/install.sh tools/deploy.sh tools/build-release.sh tools/mobile-test.sh
release:
	./tools/build-release.sh
deploy:
	./tools/deploy.sh
screenshot:
	python3 tools/capture-theme-screenshot.py
