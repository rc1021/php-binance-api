<?php
namespace Binance;

trait Margin 
{
    /**
     * 杠杆账户逐倉下单 (TRADE)
     *
     * @param $symbol string BTC
     * @param $side ENUM 订单方向 : BUY / SELL
     * @param $type ENUM 订单类型 (orderTypes, type)
     * @param $quantity DECIMAL 
     * @param $quoteOrderQty DECIMAL 
     * @param $price DECIMAL 
     * @param $stopPrice DECIMAL 与STOP_LOSS, STOP_LOSS_LIMIT, TAKE_PROFIT, 和 TAKE_PROFIT_LIMIT 订单一起使用.
     * @param $newClientOrderId string 客户自定义的唯一订单ID。若未发送自动生成。 
     * @param $icebergQty DECIMAL 与 LIMIT, STOP_LOSS_LIMIT, 和 TAKE_PROFIT_LIMIT 一起使用创建 iceberg 订单.
     * @param $newOrderRespType ENUM 设置响应: JSON. ACK, RESULT, 或 FULL; MARKET 和 LIMIT 订单类型默认为 FULL, 所有其他订单默认为 ACK.
     * @param $sideEffectType ENUM NO_SIDE_EFFECT, MARGIN_BUY, AUTO_REPAY;默认为 NO_SIDE_EFFECT.
     * @param $timeInForce ENUM GTC,IOC,FOK
     * @return array containing the response
     * @throws \Exception
     */
    public function marginIsolatedOrder(string $symbol, string $side = "BUY", string $type = "LIMIT", $quantity = null, $quoteOrderQty = null, $price = null, $stopPrice = null, $newClientOrderId = null, $icebergQty = null, $newOrderRespType = null, $sideEffectType = "NO_SIDE_EFFECT", $timeInForce = "GTC")
    {
        return $this->marginOrder($symbol, $side, $type, "TRUE", $quantity, $quoteOrderQty, $price, $stopPrice, $newClientOrderId, $icebergQty, $newOrderRespType, $sideEffectType, $timeInForce);
    }

    /**
     * 杠杆账户下单 (TRADE)
     *
     * @param $symbol string BTC
     * @param $side ENUM 订单方向 : BUY / SELL
     * @param $type ENUM 订单类型 (orderTypes, type)
     * @param $isIsolated bool 是否為逐倉交易
     * @param $quantity DECIMAL 
     * @param $quoteOrderQty DECIMAL 
     * @param $price DECIMAL 
     * @param $stopPrice DECIMAL 与STOP_LOSS, STOP_LOSS_LIMIT, TAKE_PROFIT, 和 TAKE_PROFIT_LIMIT 订单一起使用.
     * @param $newClientOrderId string 客户自定义的唯一订单ID。若未发送自动生成。 
     * @param $icebergQty DECIMAL 与 LIMIT, STOP_LOSS_LIMIT, 和 TAKE_PROFIT_LIMIT 一起使用创建 iceberg 订单.
     * @param $newOrderRespType ENUM 设置响应: JSON. ACK, RESULT, 或 FULL; MARKET 和 LIMIT 订单类型默认为 FULL, 所有其他订单默认为 ACK.
     * @param $sideEffectType ENUM NO_SIDE_EFFECT, MARGIN_BUY, AUTO_REPAY;默认为 NO_SIDE_EFFECT.
     * @param $timeInForce ENUM GTC,IOC,FOK
     * @return array containing the response
     * @throws \Exception
     */
    public function marginOrder(string $symbol, string $side = "BUY", string $type = "LIMIT", $isIsolated = "FALSE", $quantity = null, $quoteOrderQty = null, $price = null, $stopPrice = null, $newClientOrderId = null, $icebergQty = null, $newOrderRespType = null, $sideEffectType = "NO_SIDE_EFFECT", $timeInForce = "GTC")
    {
        // 类型 | 强制要求的参数
        // LIMIT | timeInForce, quantity, price
        // MARKET | quantity or quoteOrderQty
        // STOP_LOSS | quantity, stopPrice
        // STOP_LOSS_LIMIT | timeInForce, quantity, price, stopPrice
        // TAKE_PROFIT | quantity, stopPrice
        // TAKE_PROFIT_LIMIT | timeInForce, quantity, price, stopPrice
        // LIMIT_MAKER | quantity, price

        $opt = [
            "sapi" => true,
            "symbol" => $symbol,
            "side" => $side,
            "type" => $type,
            "isIsolated" => $isIsolated,
            "sideEffectType" => $sideEffectType
        ];

        switch($type) {
            case "LIMIT":
                $opt['newOrderRespType'] = 'FULL';
                $opt = array_merge($opt, compact('timeInForce', 'quantity', 'price'));
                break;
            case "MARKET":
                $opt['newOrderRespType'] = 'FULL';
                if(!is_null($quoteOrderQty))
                    $opt['quoteOrderQty'] = $quoteOrderQty;
                else
                    $opt['quantity'] = $quantity;
                break;
            case "STOP_LOSS":
                $opt['newOrderRespType'] = 'ACK';
                $opt = array_merge($opt, compact('quantity', 'stopPrice'));
                break;
            case "STOP_LOSS_LIMIT":
                $opt['newOrderRespType'] = 'ACK';
                $opt = array_merge($opt, compact('timeInForce', 'quantity', 'price', 'stopPrice'));
                break;
            case "TAKE_PROFIT":
                $opt['newOrderRespType'] = 'ACK';
                $opt = array_merge($opt, compact('quantity', 'stopPrice'));
                break;
            case "TAKE_PROFIT_LIMIT":
                $opt['newOrderRespType'] = 'ACK';
                $opt = array_merge($opt, compact('timeInForce', 'quantity', 'price', 'stopPrice'));
                break;
            case "LIMIT_MAKER":
                $opt['newOrderRespType'] = 'ACK';
                $opt = array_merge($opt, compact('quantity', 'price'));
                break;
        }

        if(!is_null($newOrderRespType))
            $opt['newOrderRespType'] = $newOrderRespType;

        $qstring = "v1/margin/order";
        return $this->httpRequest($qstring, "POST", $opt, true);
    }

    /**
     * 杠杆账户撤销订单 (TRADE)
     *
     * @param $symbol string BTC
     * @param $isIsolated bool 是否為逐倉交易
     * @param $orderId LONG 
     * @param $origClientOrderId string
     * @param $newClientOrderId string
     * @return array containing the response
     * @throws \Exception
     */
    public function marginDeleteOrder(string $symbol, $isIsolated = false, $orderId = null, string $origClientOrderId = null, string $newClientOrderId = null)
    {
        $opt = [
            "sapi" => true,
            "symbol" => $symbol,
            "isIsolated" => $isIsolated,
        ];

        if(!is_null($origClientOrderId))
            $opt['origClientOrderId'] = $origClientOrderId;
        else
            $opt['orderId'] = $orderId;

        $qstring = "v1/margin/order";
        return $this->httpRequest($qstring, "DELETE", $opt, true);
    }

    /**
     * 杠杆账户撤销单一交易对的所有挂单 (TRADE)
     *
     * @param $symbol string BTC
     * @param $isIsolated bool 是否為逐倉交易
     * @return array containing the response
     * @throws \Exception
     */
    public function marginDeleteOpenOrders(string $symbol, $isIsolated = false)
    {
        $opt = [
            "sapi" => true,
            "symbol" => $symbol,
            "isIsolated" => $isIsolated,
        ];

        $qstring = "v1/margin/openOrders";
        return $this->httpRequest($qstring, "DELETE", $opt, true);
    }

    /**
     * 杠杆逐仓账户划转 (MARGIN)
     *
     * @param $asset string 被划转的资产, 比如, BTC
     * @param $symbol string 逐仓 symbol
     * @param $transFrom string "SPOT", "ISOLATED_MARGIN"
     * @param $transTo string "SPOT", "ISOLATED_MARGIN"
     * @param $amount DECIMAL 划转数量
     * @return array containing the response
     * @throws \Exception
     */
    public function marginIsolatedTransfer(string $asset, string $symbol, string $transFrom, string $transTo, $amount)
    {
        $opt = [
            "sapi" => true,
            "asset" => $asset,
            "symbol" => $symbol,
            "transFrom" => $transFrom,
            "transTo" => $transTo,
            "amount" => $amount,
        ];

        // someone has preformated there 8 decimal point double already
        // dont do anything, leave them do whatever they want
        if (gettype($amount) !== "string") {
            // for every other type, lets format it appropriately
            $amount = number_format($amount, 8, '.', '');
        }

        if (is_numeric($amount) === false) {
            // WPCS: XSS OK.
            echo "warning: amount expected numeric got " . gettype($amount) . PHP_EOL;
        }

        $qstring = "v1/margin/isolated/transfer";
        return $this->httpRequest($qstring, "POST", $opt, true);
    }
}