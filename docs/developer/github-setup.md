# GitHub One-Time Setup — SendStack

Run these steps in order before pushing any code. Each step is a prerequisite for the next.

---

## 1. Create the GitHub organization

1. Go to https://github.com/organizations/new
2. Set the organization name to **`x1techs`**
3. Free plan is sufficient
4. Skip billing — personal account as billing email is fine for now

---

## 2. Create the team

1. Inside the `x1techs` org, go to **Teams → New team**
2. Name: **`sendstack-core`**
3. Visibility: Visible
4. Add members: Awais Irfan, Ali Akbar, Kaleem (they need to accept org invitations first — see §9)

> The team name must exactly match `@x1techs/sendstack-core` in `.github/CODEOWNERS` and `.github/dependabot.yml` or GitHub will silently ignore those references.

---

## 3. Create the repo

1. Inside the `x1techs` org, go to **Repositories → New repository**
2. Name: **`sendstack`**
3. Visibility: **Public**
4. **Do not** initialize with a README, .gitignore, or license — we already have those locally

---

## 4. Push the local repo

```bash
git remote add origin https://github.com/x1techs/sendstack.git
git push -u origin main
git push origin develop
```

Verify both `main` and `develop` appear on GitHub before setting branch protection (§6–7).

---

## 5. Import labels

Requires the [GitHub CLI](https://cli.github.com/) (`gh`). Run once from the repo root:

```bash
gh auth login   # if not already authenticated
gh label import labels.json --repo x1techs/sendstack
```

This replaces GitHub's default labels with the project label set. Existing default labels not in `labels.json` will remain — delete them manually if you want a clean slate (**Settings → Labels**).

---

## 6. Set branch protection on `main`

Go to **Settings → Branches → Add branch ruleset** (or classic protection rule), target: `main`.

| Setting | Value |
|---|---|
| Require a pull request before merging | ✅ |
| Required approving reviews | 1 |
| Dismiss stale reviews on new commits | ✅ |
| Require status checks to pass | ✅ |
| Required status checks | `phpcs`, `phpstan`, `phpunit` (add after first CI run) |
| Require branches to be up to date | ✅ |
| Do not allow bypassing the above settings | ✅ (even for admins) |
| Allow force pushes | ❌ |
| Allow deletions | ❌ |

---

## 7. Set branch protection on `develop`

Same settings as `main`, except:

| Setting | Value |
|---|---|
| Required approving reviews | 1 |
| Do not allow bypassing | ✅ |

`develop` is the integration branch — all feature PRs target it, all Dependabot PRs target it. It must stay green.

---

## 8. Add GitHub Secrets

Go to **Settings → Secrets and variables → Actions → New repository secret** for each:

| Secret name | Value |
|---|---|
| `WP_ORG_SVN_USERNAME` | Your WordPress.org username (create at wordpress.org/register if needed) |
| `WP_ORG_SVN_PASSWORD` | Your WordPress.org SVN password — set separately in your wp.org profile under **Edit Profile → SVN Password** (it is NOT your login password) |
| `INSTAWP_API_KEY` | API key from your [InstaWP](https://instawp.io) account dashboard |
| `INSTAWP_TEMPLATE_ID` | The numeric ID of your InstaWP base template (shown in the template URL) |

> Secrets are org-level or repo-level. For now, repo-level is fine. Migrate to org-level if more repos share the same credentials later.

---

## 9. Invite team members

1. Go to **x1techs org → People → Invite member**
2. Invite Ali Akbar and Kaleem by GitHub username or email
3. They must accept the email invitation before they appear as org members
4. Once they've accepted, go to **Teams → sendstack-core → Members → Add a member** and add them

> Ali and Kaleem will receive review requests via CODEOWNERS only after they are org members AND members of the `sendstack-core` team.