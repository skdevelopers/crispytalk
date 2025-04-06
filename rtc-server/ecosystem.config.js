module.exports = {
  apps: [
    {
      name: 'rtc-server',
      script: 'index.js', // or your correct file name 
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
    },
  ],
};
