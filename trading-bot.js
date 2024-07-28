const express = require('express');
const Binance = require('binance-api-node').default;

const app = express();
const port = 3000;

// Настройка клиента Binance API
const client = Binance({
  apiKey: window.api,
  apiSecret: window.secret,
});

// Middleware для обработки статических файлов
app.use(express.static('public'));

// Эндпоинт для получения баланса
app.get('/api/balance', async (req, res) => {
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
  const symbol = req.params.symbol;
  try {
    const ticker = await client.prices({ symbol });
    res.json(ticker);
  } catch (error) {
    console.error('Ошибка получения цены:', error);
    res.status(500).json({ error: 'Ошибка получения цены', details: error.message });
  }
});

// Эндпоинт для получения последних операций
app.get('/api/trades/:symbol', async (req, res) => {
  const symbol = req.params.symbol;
  try {
    const trades = await client.myTrades({ symbol });
    res.json(trades);
  } catch (error) {
    console.error('Ошибка получения операций:', error);
    res.status(500).json({ error: 'Ошибка получения операций', details: error.message });
  }
});

// Запуск сервера
app.listen(port, () => {
  console.log(`Сервер запущен на http://localhost:${port}`);
});