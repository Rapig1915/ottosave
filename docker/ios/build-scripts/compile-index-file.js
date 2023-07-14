import dotenv from 'dotenv';
dotenv.config();
import Mustache from 'mustache';
import path from 'path';
import fs from 'fs';
import {fileURLToPath} from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const envOptions = {
    APP_URL: process.env.APP_URL,
    RECAPTCHA_DISABLED: process.env.RECAPTCHA_DISABLED || true,
    RECAPTCHA_SITE_KEY: process.env.RECAPTCHA_SITE_KEY
};
const indexTemplate = fs.readFileSync(path.resolve(__dirname, '../../../resources/views/mobile.index.html'));
const renderedIndexFile = Mustache.render(indexTemplate.toString(), envOptions);
fs.writeFileSync(path.resolve(__dirname, '../../../public/ios_build/index.html'), renderedIndexFile);
