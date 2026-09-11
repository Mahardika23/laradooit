# Triage labels

Five labels carry the whole triage vocabulary for this repository. Nothing else is a triage signal, and an issue should carry exactly one of them.

| Label             | Meaning                                                                               |
| ----------------- | ------------------------------------------------------------------------------------- |
| `needs-triage`    | The maintainer has not evaluated this yet. Do not start building it.                   |
| `needs-info`      | Waiting on the reporter. Blocked until they answer, however obvious the fix looks.     |
| `ready-for-agent` | Fully specified. An agent can pick it up and build it from the body as written.        |
| `ready-for-human` | Needs a human: a judgement call, a credential, an external dashboard, or a design eye. |
| `wontfix`         | Considered and declined. Closed, and not reopened without new information.             |

When a skill refers to a triage role in the abstract — "apply the agent-ready label", "sweep the untriaged queue" — it means the label in the left-hand column.

An issue is only safe to implement unprompted when it is labelled `ready-for-agent` and none of its blockers are open. Anything else, ask first.
