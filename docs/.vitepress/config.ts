import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'cakephp-captcha',
  description: 'Add honeypots, math challenges, and token checks to CakePHP forms.',
  base: '/cakephp-captcha/',
  cleanUrls: true,
  srcExclude: ['README.md', 'Active.md', 'Passive.md', 'Admin.md'],
  lastUpdated: true,
  head: [
    ['link', { rel: 'icon', href: '/cakephp-captcha/favicon.svg', type: 'image/svg+xml' }],
  ],
  sitemap: { hostname: 'https://dereuromark.github.io/cakephp-captcha/' },
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Installation', link: '/guide/installation' },
      { text: 'Getting started', link: '/guide/getting-started' },
      { text: 'Reference', link: '/reference/configuration' },
    ],
    sidebar: [
      {
        text: 'Guide',
        items: [
          { text: 'Overview', link: '/guide/' },
          { text: 'Installation', link: '/guide/installation' },
          { text: 'Getting started', link: '/guide/getting-started' },
          { text: 'Upgrading', link: '/guide/upgrading' },
          { text: 'Contributing', link: '/guide/contributing' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'Configuration', link: '/reference/configuration' },
          { text: 'Active captchas', link: '/reference/active' },
          { text: 'Passive captchas', link: '/reference/passive' },
          { text: 'Admin interface', link: '/reference/admin' },
        ],
      },
    ],
    search: { provider: 'local' },
    socialLinks: [{ icon: 'github', link: 'https://github.com/dereuromark/cakephp-captcha' }],
    editLink: {
      pattern: 'https://github.com/dereuromark/cakephp-captcha/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },
    footer: { message: 'Released under the MIT License.' },
  },
})
