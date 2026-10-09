# Issue tracker: GitHub

Every ticket, spec, and bug report for this repository lives as a GitHub issue in `Mahardika23/laradooit`. Use the `gh` CLI for all of it; run inside a clone and `gh` infers the repository from the git remote.

## Operations

- **Read an issue**: `gh issue view <number> --comments`. Always read the comments; a ticket's scope is often narrowed or corrected there rather than in the body.
- **List issues**: `gh issue list --state open --label ready-for-agent --json number,title,labels`.
- **Create an issue**: `gh issue create --title "..." --body "..."`, using a heredoc for the body.
- **Comment**: `gh issue comment <number> --body "..."`.
- **Label**: `gh issue edit <number> --add-label "..."` and `--remove-label "..."`.
- **Close**: `gh issue close <number> --comment "..."`.

When a skill says "publish to the issue tracker", create a GitHub issue. When it says "fetch the relevant ticket", run `gh issue view <number> --comments`.

## How tickets are shaped

A release is written up as one spec issue: problem statement, user stories, implementation decisions, testing decisions, and what is out of scope. The work is then broken into one implementation ticket per feature area, in build order. Each implementation ticket opens with a `## Parent` section naming its spec, carries a `## What to build` paragraph and a checklist of acceptance criteria, and lists what it is blocked by.

Treat the acceptance criteria as the contract. The spec explains why and supplies context the ticket assumes; the criteria are what the work is checked against. Where the two appear to disagree, follow the ticket and say what you noticed.

Dependencies between tickets use GitHub's native issue dependencies, so a ticket's blockers are visible in the UI and in `gh issue view`. A ticket is ready when every blocker is closed. Add an edge with:

```sh
gh api --method POST repos/Mahardika23/laradooit/issues/<blocked>/dependencies/blocked_by \
  -F issue_id=$(gh api repos/Mahardika23/laradooit/issues/<blocker> --jq .id)
```

The `issue_id` is the blocker's numeric database id, not its `#number`.

## Pull requests

Pull requests are not a triage surface here; they are how finished work arrives. Reference the issue the work came from in the pull request body, and let the maintainer merge it. Issues and pull requests share one number space on GitHub, so a bare `#42` may be either: resolve it with `gh pr view 42` and fall back to `gh issue view 42`.
