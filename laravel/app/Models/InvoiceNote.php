<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('invoice_notes', timestamps: false)]
#[Fillable(['invoice_no', 'notes'])]
class InvoiceNote extends Model {}
