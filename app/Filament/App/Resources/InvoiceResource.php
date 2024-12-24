<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\BelongsToSelect;
use Filament\Tables\Actions\Action;
use App\Filament\App\Resources\InvoiceResource\Pages;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                BelongsToSelect::make('customer_id')
                    ->relationship('customer', 'customer_name')
                    ->required()
                    ->searchable(),
                TextInput::make('invoice_number')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($record) => $record !== null),
                DatePicker::make('invoice_date')
                    ->required(),
                DatePicker::make('due_date'),
                TextInput::make('total_amount')
                    ->numeric()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        if ($get('tax_rate_id')) {
                            $taxRate = \App\Models\TaxRate::find($get('tax_rate_id'));
                            $taxAmount = $state * ($taxRate->rate / 100);
                            $set('tax_amount', $taxAmount);
                        }
                    }),
                BelongsToSelect::make('tax_rate_id')
                    ->relationship('taxRate', 'name')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        if ($state && $get('total_amount')) {
                            $taxRate = \App\Models\TaxRate::find($state);
                            $taxAmount = $get('total_amount') * ($taxRate->rate / 100);
                            $set('tax_amount', $taxAmount);
                        }
                    }),
                TextInput::make('tax_amount')
                    ->numeric()
                    ->disabled(),
                Select::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ])
                    ->required(),
                Textarea::make('notes')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.customer_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('download')
                    ->icon('heroicon-o-document-download')
                    ->action(fn (Invoice $record) => response()->streamDownload(
                        fn () => print($record->generatePDF()),
                        "invoice_{$record->invoice_number}.pdf"
                    )),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
