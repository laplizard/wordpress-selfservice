{# respond to change in self-service config #}
refresh_pillar:
  local.saltutil.refresh_pillar:
    - tgt: '*'

selfservice_update:
  local.state.sls:
   - tgt: '*'
   - arg:
      - selfservice.sites.wordpress

apache_update:
  local.state.sls:
   - tgt: '*'
   - arg:
      - selfservice.vhosts.standard
