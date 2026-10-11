<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('bank_details', timestamps: false)]
#[Fillable(['invoice_id', 'account_name', 'bank_name', 'account_number', 'branch'])]
class BankDetail extends Model {}
