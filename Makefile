COMPOSE := docker compose
LOADGEN := $(COMPOSE) run --rm loadgen
CONSOLE := $(COMPOSE) exec php php bin/console
SHOP := http://shop.localhost:8800
PRIVATE := http://localhost:6060

.DEFAULT_GOAL := help
.PHONY: help check prepare up down restart step solution ember load logs \
	fail request-id \
	slow profile spx \
	burn stats \
	leak threads \
	opcache classic \
	orders scale queue metrics \
	load-fixed status daemon scrape snapshot deploy-buggy deploy-fixed diff diff-reverse

help: ## Show this help
	@grep -hE '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

# Setup

check: ## Check that the tools of the workshop are installed
	@for tool in docker ember git make; do \
		command -v $$tool >/dev/null 2>&1 && echo "  ok       $$tool" || echo "  MISSING  $$tool"; \
	done

prepare: ## Download and build everything, before the conference Wi-Fi
	$(COMPOSE) pull database
	$(COMPOSE) --profile tools pull loadgen
	$(COMPOSE) build
	$(COMPOSE) run --rm --no-deps --entrypoint composer php install --prefer-dist --no-progress --no-interaction

up: ## Start the shop, its database and the Messenger worker
	$(COMPOSE) up -d --wait
	@echo ""
	@echo "  Shop    : curl $(SHOP)/products/1"
	@echo "  Admin   : http://localhost:2019 (Ember connects here)"
	@echo "  Private : $(PRIVATE) (SPX and metrics, never routed from the shop)"

down: ## Stop and remove the containers
	$(COMPOSE) down --remove-orphans

restart: ## Restart PHP and the worker so they load your code and config
	$(COMPOSE) restart php worker
	$(COMPOSE) up -d --wait php

step: ## Move to the start of a step, your changes go to a git stash: make step N=04
	@if [ -n "$$(git status --porcelain)" ]; then \
		git stash push --include-untracked --quiet -m "my work before step-$(N)"; \
		echo "  Your changes are saved: git stash list, then git stash pop to get them back"; \
	fi
	git checkout --quiet step-$(N)
	$(MAKE) restart

solution: ## Show the solution of a step as a diff: make solution N=03
	git diff step-$(N) step-$(N)-solution

ember: ## Open Ember on the shop
	$(COMPOSE) logs -f --no-log-prefix --tail 0 php < /dev/null | ember --stdin-logs

load: ## Send baseline traffic to the shop (Ctrl+C to stop)
	$(LOADGEN)

load-fixed: ## Send baseline traffic at a fixed rate, whatever the response time
	$(LOADGEN) --rate=$(or $(RATE),150)

logs: ## Follow the logs of the shop
	$(COMPOSE) logs -f --no-log-prefix php

# Step 02: logs

fail: ## Make the database disappear for 30 seconds
	$(COMPOSE) stop database
	@echo "  Database stopped, back in 30 seconds"
	@sleep 30
	$(COMPOSE) start database

request-id: ## Show the request ID FrankenPHP gave to one request
	curl -si $(SHOP)/products/1 | grep -i x-request-id

# Step 03: latency

slow: ## Click "sync now" in the back office once per second
	$(LOADGEN) -c 1 --pause=1000 /sync

profile: ## Profile N requests with SPX (20 by default) and link the slowest inside PHP: make profile URL=/sync
	@keys=""; \
	for i in $$(seq 1 $(or $(N),20)); do \
		keys="$$keys $$(curl -s -o /dev/null -D - -H 'X-Spx-Profile: 1' '$(SHOP)$(URL)' | tr -d '\r' | sed -n 's/^[Xx]-[Ss]px-[Rr]eport: //p')"; \
		sleep 0.1; \
	done; \
	if [ -z "$$(echo $$keys)" ]; then \
		echo "  No SPX report: the profiler subscriber is not written yet (TODO step-03), or make restart was skipped"; \
		exit 1; \
	fi; \
	$(COMPOSE) exec -T php php -r '$$slowest = null; foreach (array_slice($$argv, 1) as $$key) { $$report = json_decode(file_get_contents("/tmp/spx/$$key.json"), true); if (null === $$slowest || $$report["wall_time_ms"] > $$slowest["wall_time_ms"]) { $$slowest = $$report; } } printf("  Slowest of %d profiles: %.1f ms inside PHP\n  $(PRIVATE)/?SPX_KEY=workshop&SPX_UI_URI=/report.html&key=%s\n", $$argc - 1, $$slowest["wall_time_ms"] / 1000, $$slowest["key"]);' $$keys
	@echo "  First time in this browser? Open $(PRIVATE)/?SPX_KEY=workshop&SPX_UI_URI=/ once, the report page needs its cookie"

spx: ## Print the address of the SPX web UI
	@echo "  $(PRIVATE)/?SPX_KEY=workshop&SPX_UI_URI=/"

# Step 05: CPU

burn: ## Search the catalog in a loop
	$(LOADGEN) -c 20 '/search?q=lamp'

stats: ## Compare the CPU of the shop, the worker and the database
	docker stats $$($(COMPOSE) ps -q)

# Step 06: memory

leak: ## Ask for recommendations as new visitors, in a loop
	$(LOADGEN) -c 4 '/recommendations?visitor={uuid}'

threads: ## Print the memory of every PHP thread every 2 seconds
	@trap 'exit 130' INT; while true; do \
		$(COMPOSE) exec -T php php -r '$$s = json_decode(file_get_contents("http://localhost:2019/frankenphp/threads"), true); foreach ($$s["ThreadDebugStates"] as $$t) { printf("  #%-2d %-45s %6.1f MB  %d requests\n", $$t["Index"], $$t["Name"], $$t["MemoryUsage"] / 1048576, $$t["RequestCount"]); }'; \
		echo; sleep 2; \
	done

# Step 07: OPcache

opcache: ## Print the OPcache status of the shop
	curl -s $(PRIVATE)/opcache

classic: ## Send traffic through the private port, where PHP runs without workers
	$(LOADGEN) --target=http://php:6060 --host=localhost -c 4 '/products/{id}'

# Step 08: Messenger

orders: ## Place orders in a loop
	$(LOADGEN) -c 2 --pause=100 'POST /orders'

scale: ## Run N Messenger workers: make scale N=6
	$(COMPOSE) up -d --no-recreate --scale worker=$(N)

queue: ## Count the messages waiting in each transport
	$(CONSOLE) messenger:stats

metrics: ## Read the metrics of the shop
	curl -s $(PRIVATE)/metrics

# Step 09: production

status: ## One-line health summary of FrankenPHP
	ember status

daemon: ## Run Ember headless, exporting Prometheus metrics on :9191
	ember --daemon --expose :9191

scrape: ## Read what Prometheus would scrape from Ember
	curl -s localhost:9191/metrics | grep -E '^(ember_host|frankenphp)_'

snapshot: ## Record 25s of Ember JSON into $(NAME).json (NAME=before or NAME=after)
	ember --json --interval 10s --timeout 25s > $(NAME).json
	@echo "  $$(wc -l < $(NAME).json | tr -d ' ') snapshots in $(NAME).json, ember diff uses the last one, which covers 10 seconds"

deploy-buggy: ## Deploy the catalog of step 03, which holds its lock during the supplier call
	git diff step-03 step-03-solution -- app/src/Catalog/Catalog.php | git apply --reverse
	$(MAKE) restart

deploy-fixed: ## Deploy the current catalog, with the lock fix
	git checkout -- app/src/Catalog/Catalog.php
	$(MAKE) restart

diff: ## Compare before.json and after.json, exit code 1 on regression
	ember diff before.json after.json

diff-reverse: ## Same comparison the other way round, to see a regression fail
	ember diff after.json before.json
