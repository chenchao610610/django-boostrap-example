from django.conf.urls import patterns, include, url
from django.contrib.staticfiles.urls import staticfiles_urlpatterns

from django.contrib import admin
from HelloWorld.view import hello
from HelloWorld.view import insertData
from HelloWorld.view import getData
from HelloWorld.view import deleteData
from HelloWorld.view import index
from HelloWorld.view import home
from HelloWorld.view import stat
from HelloWorld.view import test
from HelloWorld.view import movie
from HelloWorld.view import login
admin.autodiscover()


urlpatterns = patterns('',
    # Examples:
    # url(r'^$', 'HelloWorld.views.home', name='home'),
    # url(r'^blog/', include('blog.urls')),
    #('^hello/$', hello),
    url(r'^login/$', login),
    url(r'^signup/$', 'HelloWorld.view.signup'),
    url(r'^index/$', index),
    url(r'^faq/$', 'HelloWorld.view.faq'),
    url(r'^stat/$', stat),
    url(r'^home/$', home),
    url(r'^$', home),
    url(r'^test/$', test),
    url(r'^movie/$', movie),
    url(r'^hello/',   'HelloWorld.view.hello', name='hello'),
    url(r'^insert/$', insertData),
    url(r'^get/$', getData),
    url(r'^delete/$', deleteData),
    url(r'^admin/', include(admin.site.urls)),
)

urlpatterns += staticfiles_urlpatterns()
