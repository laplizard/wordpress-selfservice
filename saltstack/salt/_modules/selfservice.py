# salt execution module for selfservice
import hashlib
import base64
#import logging

def password(user,context=''):
    '''
    generate/get local password for specific user/context
    '''
    m = hashlib.md5()
    m.update( __salt__['pillar.get']('selfservice:seed','E4cq8KcZ') )
    m.update( '/' )
    m.update( context )
    m.update( '/' )
    m.update( user )
    return base64.b64encode( m.digest() )

def safetext(text):
    '''
    filter text to be safe in shell argument, e.g. wp title.
    '''
    keepcharacters = (' ','.','_',',','-', ':', '/', '=', '@')
    return "".join(c for c in text if c.isalnum() or c in keepcharacters).rstrip()

