import WeatherController from './WeatherController'
import Web from './Web'
import Settings from './Settings'

const Controllers = {
    WeatherController: Object.assign(WeatherController, WeatherController),
    Web: Object.assign(Web, Web),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers