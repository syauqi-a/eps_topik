<?php

namespace App\Filament\Teacher\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use App\Models\Question;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Teacher\Resources\QuestionResource\Pages;
use App\Filament\Teacher\Resources\QuestionResource\RelationManagers;
use App\Filament\Teacher\Resources\QuestionResource\RelationManagers\ChoicesRelationManager;
use Filament\Resources\Concerns\Translatable;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Support\HtmlString;
use League\CommonMark\GithubFlavoredMarkdownConverter as Converter;

class QuestionResource extends Resource
{
    use Translatable;

    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Teaching';

    public static function form(Form $form): Form
    {
        return static::getQuestionForm($form);
    }

    public static function table(Table $table): Table
    {
        return static::getQuestionTable($table)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ChoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }

    public static function getQuestionForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make([
                    TiptapEditor::make('content')
                        ->columnSpanFull()
                        ->extraInputAttributes(['style' => 'min-height: 12rem;'])
                        ->tools([
                            'heading', 'bullet-list', 'ordered-list', 'checked-list', 'blockquote', 'hr', '|',
                            'bold', 'italic', 'strike', 'underline', 'superscript', 'subscript', 'lead', 'small', 'color', 'highlight', 'align-left', 'align-center', 'align-right', '|',
                            'link', 'media', 'table', 'grid-builder', 'details', '|', 'source',
                        ])
                        ->floatingMenuTools(['media', 'table'])
                        ->acceptedFileTypes(['image/*'])
                        // ->disk('s3')
                        ->directory('images/questions'),
                    Forms\Components\Select::make('question_type')
                        ->options(Question::questionTypes())
                        ->required()
                        ->native(false)
                        ->live(),
                    Forms\Components\TagsInput::make('tags')
                        ->suggestions(Question::tags())
                        ->required(),
                    Forms\Components\FileUpload::make('question_audio')
                        ->acceptedFileTypes(['audio/*'])
                        // ->disk('s3')
                        ->directory('audios/questions')
                        ->visibility('public')
                        ->visible(fn (Get $get) => $get('question_type') === '듣기')
                        ->required(),
                ])->columns(2),
                Forms\Components\Section::make([
                    Forms\Components\Repeater::make('choices')
                        ->schema(ChoicesRelationManager::getChoiceForm())
                        ->grid(2)
                        ->collapsible()
                        ->itemLabel(function (array $state): ?string {
                            if ($state['text']) {
                                $badge = $state['is_correct'] ? '✔' : '❌';
                                return $state['text'] . ' ' . $badge;
                            } else {
                                return null;
                            }
                        })
                        ->defaultItems(4)
                        ->helperText(new HtmlString(
                            'To add translations, please go to the <b>edit page</b> after the creation process is successful.'
                        )),
                ])->hiddenOn('edit'),
            ]);
    }

    public static function getQuestionTable(Table $table): Table
    {
        return $table
            ->query(fn () => Question::where('created_by.uid', auth()->id()))
            ->columns([
                Tables\Columns\TextColumn::make('content')
                    ->limit(50)
                    ->wrap()
                    ->formatStateUsing(fn ($state) => strip_tags(
                        (new Converter())->convert($state)->getContent()
                    )),
                Tables\Columns\TextColumn::make('question_type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('tags')
                    ->badge(),
                Tables\Columns\IconColumn::make('images')
                    ->getStateUsing(fn (Question $record) => $record->question_images != null)
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                Tables\Columns\IconColumn::make('question_audio')
                    ->label('Audio')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                    ->default(false)
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->filters([
                //
            ]);
    }
}
