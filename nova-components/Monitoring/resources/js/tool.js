

Nova.booting((Vue, router, store) => {
  Vue.component('monitoring', require('./components/Tool').default)
  Vue.use(require('vue-pusher'), {
    api_key: 'ab6249534fc452e7dfe5',
    options: {
        cluster: 'ap2',
        encrypted: true,
    }
  });
})


