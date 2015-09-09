{# respond to change in self-service config #}

selfservice_update:
  local.state.sls:
   - tgt: '*'
   - arg:
      - selfservice.update
