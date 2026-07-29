# Public publication

This project was prepared with a clean, unrelated Git history so no private commits, pull requests, branch names, workflow logs, or deleted source files are inherited.

Before publishing, run:

```bash
python3 tools/scan-secrets.py
python3 tools/validate.py
```

With an authenticated GitHub CLI session, create the public repository and push the single clean history:

```bash
tools/publish-new-public-repo.sh persian-barbershop-wordpress
```

The script refuses to publish a dirty working tree and reruns all available checks before creating the repository.
