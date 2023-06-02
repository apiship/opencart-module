# List of folders
FOLDERS := 2.1 2.3 3.x

# Default version
VERSION ?= dev

# Rule for archiving folders
.PHONY: build
build:
	@for folder in $(FOLDERS); do \
		zip -r "apiship-$(VERSION)_$$folder.ocmod.zip" $$folder -x "*.DS_Store" -x "*.git*" -x "*/.git/*"; \
	done

