# MDHI GitHub → Bluehost deployment

Confirmed repository: `/home1/mdhiglob/repositories/mdhiglobal-website`

Confirmed production document root: `/home1/mdhiglob/public_html`

## First deployment

1. Before starting, make a full backup of the existing MDHI website (including hidden files) and download it. The script's automatic backups cover only files it will overwrite, not the entire account or other websites.
2. Merge this deployment PR into `main`.
3. In Bluehost → Git Version Control → Manage, confirm the repository path above and the checked-out branch `main`.
4. Select **Pull or Deploy → Update from Remote**. Confirm the new commit has arrived.
5. Select **Deploy HEAD Commit**. This is a PRODUCTION publish to the confirmed document root.
6. Check the deployment output for `Deployment completed` and note the backup directory. Confirm the Last Deployed SHA matches the GitHub commit.
7. Hard-refresh the website; inspect the homepage/header, About workshop map, Gallery infographic modals, Products and Quality images. Check `/mg25.pdf` and test the contact form for actual mail receipt.

GitHub merges alone do not publish this pull-based setup. Do not edit files directly in the Bluehost repository; local changes prevent deployment.

## Publish scope and backups

- All tracked root-level HTML and approved static assets under `css/`, `js/`, `images/`, `suyee/`, `scripts/` are published. JPEG and WebP are copied exactly as stored; no image conversion runs on Bluehost.
- PHP, `.htaccess`, PDFs, `.git`, deployment scripts and documentation are excluded. Existing PHP mail handlers and `/mg25.pdf` must already be present and working on the server.
- No stale files or unrelated directories (including other websites under `public_html`) are removed. Existing directories keep their permissions; new directories use 755 and published files use 644.
- A clean `main` checkout is required. A lock prevents two runs of this script overlapping. Symlink paths are refused.
- Before any website file changes, existing publish files are copied into a private snapshot under `/home1/mdhiglob/deploy-backups/mdhiglobal/`.
- Snapshots include `previous/`, `overwritten.txt`, `added.txt`, commit, target and SHA-256 manifest. `verification.txt` verifies published bytes. `status.txt` records SUCCESS or FAILED after publication starts.
- Assets publish before HTML; each file is replaced by a same-directory rename. The entire site update is not atomic. A failed run may leave a partial update; use its snapshot to restore.
- Backups accumulate; review space periodically. Nothing is automatically pruned.

## Optional preview / inspection (Bluehost Terminal)

```bash
cd /home1/mdhiglob/repositories/mdhiglobal-website
bash deploy/deploy.sh --dry-run
bash deploy/deploy.sh --preview
```

Dry-run lists files without copying. Preview writes to `/home1/mdhiglob/public_html/mdhi-preview` using the same manifest and backup procedure; view `https://mdhiglobal.com/mdhi-preview/`. The cPanel Deploy button still targets production, not preview.

Preview does not copy PHP or PDFs. Root-relative URLs may use production files, so it does not independently validate brochure or mail handling. If the preview directory has an existing purpose, do not run preview there.

## Restore / rollback

For a failed or unwanted deployment, select its snapshot using the recorded time and source SHA. Read `target.txt` to distinguish production from preview. Through File Manager, restore the contents of `previous/` into that exact target, preserving subdirectories. These are the files that existed before the run. Restore file permissions if your copy operation changes them.

Files listed in `added.txt` did not exist before the run; leave them in place initially. This restore does not remove newly added files and is not an exact whole-directory rollback. Use the full pre-deployment backup if exact restoration is needed. Never delete other site folders to roll back MDHI.

For an older GitHub release, revert the relevant changes through a new PR, merge into main, then Update from Remote → Deploy HEAD Commit. No branch switching or forced resets are required.

## Future PRs

Existing pages and images are picked up automatically; contributors do not need to edit `.cpanel.yml` for each content PR. Update the deployment allowlist when adding a new asset directory, file type or backend dependency. Review mail handlers and server configuration separately before changing publish scope.
