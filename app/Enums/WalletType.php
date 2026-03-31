<?php

namespace App\Enums;

enum WalletType: string
{
    case Bank = 'bank';
    case EWallet = 'e_wallet';
    case Cash = 'cash';
    case CreditCard = 'credit_card';
}
