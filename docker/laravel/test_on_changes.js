import chokidar from 'chokidar';
import path from 'path';
import ChildProcess from 'child_process';
import {fileURLToPath} from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const appDir = path.resolve(__dirname, '../../app');
const testDir = path.resolve(__dirname, '../../tests');
const routesDir = path.resolve(__dirname, '../../routes');
const configDir = path.resolve(__dirname, '../../config');

const watchedDirectories = [
    appDir,
    testDir,
    routesDir,
    configDir
];

var watcher = chokidar.watch(watchedDirectories, { usePolling: true, ignoreInitial: true, interval: 300, binaryInterval: 1000 });
watcher.on('add', runPhpUnit).on('change', runPhpUnit).on('unlink', runPhpUnit);

function runPhpUnit(){
    console.log('Files changed, running phpunit...');
    const phpunit = ChildProcess.spawn('vendor/bin/phpunit', [], { cwd: '/var/www/html' });
    phpunit.stdout.on('data', data => {
        process.stdout.write(`${data}`);
    });
    phpunit.stderr.on('data', error => {
        console.error(`${error}`);
    });
    phpunit.on('close', exit_code => {
        console.log('PHPUnit exited with code: ' + exit_code);
        console.log('');
    });
}
