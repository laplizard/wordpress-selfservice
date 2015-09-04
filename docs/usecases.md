# Use Cases

## Use Case 1

- Alice is given an (author) account on selfservice
- Alice creates a new (custom post) Website, specifying title, description, slug (optional?)
- Alice publishes the Website (post)
- (ideally) the act of publication notifies saltstack (custom beacon -> event?, or suid script -> event?)
- SaltStack dumps Website target states from selfservice (title, description, slug, status, id, admin user (Alice), admin email, admin password)
- SaltStack creates new Websites and configures them as specified
- SaltStack updates existing Websites
- SaltStack archives/deletes removed websites

Notes:

- all Website files to be kept under a id-based directory
- ID should be (very) stable
- above implies default (templated?) WP version, plugin(s) and initial configuration

