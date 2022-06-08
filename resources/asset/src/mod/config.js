module.exports = function (mod) {
    let config = {
        cdn: `/vendor/${mod}`,
        dist: `./../../../../public/vendor/${mod}`,
        distAsset: './../../Asset',
        apps: [
            'entry',
        ],
    }
    if ('App' === mod) {
        config.dist = `./../../public/vendor/${mod}`
        config.distAsset = './../Asset-build'
    }
    switch (process.platform) {
        case 'win32':
            config.platform = 'windows'
            break
        default:
            config.platform = 'linux'
            break
    }
    return config
}
