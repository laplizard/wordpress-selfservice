{# respond to change in self-service config #}
refresh_pillar:
  local.saltutil.refresh_pillar:
    - tgt: '*'
