const express = require('express');
const bodyParser = require('body-parser');
const Binance = require('binance-api-node').default;

const app = express();
const port = 3000;

let client;

// Middleware для обработки статических файлов и JSON-тел
app.use(express.static('public'));
app.use(bodyParser.json());

// Эндпоинт для установки API ключа и секрета
app.post('/api/set-credentials', (req, res) => {
  const { apiKey, apiSecret } = req.body;

  if (!apiKey || !apiSecret) {
    return res.status(400).json({ error: 'API key and secret are required' });
  }

  // Настройка клиента Binance API
  client = Binance({
    apiKey: apiKey,
    apiSecret: apiSecret,
  });

  res.status(200).json({ message: 'Credentials set successfully' });
});

// Эндпоинт для получения баланса
app.get('/api/balance', async (req, res) => {
  if (!client) {
    return res.status(400).json({ error: 'API client is not set' });
  }

  try {
    const accountInfo = await client.accountInfo();
    const usdtBalance = accountInfo.balances.find(asset => asset.asset === 'USDT');
    if (usdtBalance) {
      res.json(usdtBalance);
    } else {
      res.json({ asset: 'USDT', free: '0' });
    }
  } catch (error) {
    console.error('Ошибка получения баланса:', error);
    res.status(500).json({ error: 'Ошибка получения баланса', details: error.message });
  }
});

// Эндпоинт для получения текущих цен
app.get('/api/price/:symbol', async (req, res) => {
  if (!client) {
    return res.status(400).json({ error: 'API client is not set' });
  }

  const symbol = req.params.symbol;
  try {
    const ticker = await client.prices({ symbol: symbol });
    res.json(ticker);
    
//
client.myTrades({
  symbol: 'BTCUSDT'
}).then(trades => {
  console.log(trades);
}).catch(error => {
  console.error(error);
});
//

  } catch (error) {
    console.error('Ошибка получения цены:', error);
    res.status(500).json({ error: 'Ошибка получения цены', details: error.message });
  }
});

// Запуск сервера
app.listen(port, () => {
  console.log(`Сервер запущен на http://localhost:${port}`);
});
