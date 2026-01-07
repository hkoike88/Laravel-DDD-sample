<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Presentation\HTTP\Controllers;

use App\Models\SearchLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Packages\Domain\Book\Application\UseCases\Commands\CreateBook\CreateBookCommand;
use Packages\Domain\Book\Application\UseCases\Commands\CreateBook\CreateBookHandler;
use Packages\Domain\Book\Application\UseCases\Queries\SearchBooks\SearchBooksHandler;
use Packages\Domain\Book\Application\UseCases\Queries\SearchBooks\SearchBooksQuery;
use Packages\Domain\Book\Domain\Repositories\BookRepositoryInterface;
use Packages\Domain\Book\Domain\ValueObjects\ISBN;
use Packages\Domain\Book\Presentation\HTTP\Requests\CheckIsbnRequest;
use Packages\Domain\Book\Presentation\HTTP\Requests\CreateBookRequest;
use Packages\Domain\Book\Presentation\HTTP\Requests\SearchBooksRequest;
use Packages\Domain\Book\Presentation\HTTP\Resources\BookCollectionResource;
use Packages\Domain\Book\Presentation\HTTP\Resources\BookResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * 蔵書コントローラ
 *
 * 蔵書関連のHTTPリクエストを処理するコントローラ。
 */
class BookController extends Controller
{
    /**
     * コンストラクタ
     *
     * @param  SearchBooksHandler  $searchBooksHandler  蔵書検索ハンドラ
     * @param  CreateBookHandler  $createBookHandler  蔵書登録ハンドラ
     * @param  BookRepositoryInterface  $bookRepository  蔵書リポジトリ
     */
    public function __construct(
        private readonly SearchBooksHandler $searchBooksHandler,
        private readonly CreateBookHandler $createBookHandler,
        private readonly BookRepositoryInterface $bookRepository,
    ) {}

    /**
     * 蔵書一覧を取得（検索）
     *
     * 指定された条件で蔵書を検索し、ページネーション付きの結果を返却。
     * 検索実行後、匿名の検索統計ログを記録。
     *
     * @param  SearchBooksRequest  $request  検索リクエスト
     * @return JsonResponse 蔵書一覧レスポンス
     */
    public function index(SearchBooksRequest $request): JsonResponse
    {
        $query = new SearchBooksQuery(
            title: $request->title(),
            author: $request->author(),
            isbn: $request->isbn(),
            page: $request->page(),
            perPage: $request->perPage(),
        );

        $collection = $this->searchBooksHandler->handle($query);

        // 検索統計ログを記録（匿名）
        SearchLog::record(
            title: $request->title(),
            author: $request->author(),
            isbn: $request->isbn(),
            resultCount: $collection->totalCount,
        );

        return (new BookCollectionResource($collection))->response();
    }

    /**
     * 蔵書を登録
     *
     * 新規蔵書をシステムに登録し、登録された蔵書情報を返却。
     * 認証済み職員のIDを登録者情報として記録する。
     *
     * @param  CreateBookRequest  $request  登録リクエスト
     * @return JsonResponse 登録された蔵書レスポンス（201 Created）
     */
    public function store(CreateBookRequest $request): JsonResponse
    {
        /** @var \Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord $staff */
        $staff = $request->user();

        $command = new CreateBookCommand(
            title: $request->title(),
            staffId: $staff->id,
            author: $request->author(),
            isbn: $request->isbn(),
            publisher: $request->publisher(),
            publishedYear: $request->publishedYear(),
            genre: $request->genre(),
        );

        $book = $this->createBookHandler->handle($command);

        return (new BookResource($book))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * ISBN重複をチェック
     *
     * 指定されたISBNが既に登録されているかをチェックし、
     * 同一ISBNの蔵書数を返却する。
     *
     * @param  CheckIsbnRequest  $request  チェックリクエスト
     * @return JsonResponse チェック結果（exists: boolean, count: number）
     */
    public function checkIsbn(CheckIsbnRequest $request): JsonResponse
    {
        $isbn = ISBN::fromString($request->isbn());
        $books = $this->bookRepository->findByIsbn($isbn);
        $count = count($books);

        return response()->json([
            'exists' => $count > 0,
            'count' => $count,
        ]);
    }

    /**
     * 蔵書詳細を取得
     *
     * 指定されたIDの蔵書情報を返却する。
     *
     * @param  string  $id  蔵書ID（ULID形式）
     * @return JsonResponse 蔵書詳細レスポンス
     *
     * @throws \Packages\Domain\Book\Domain\Exceptions\BookNotFoundException 蔵書が存在しない場合
     */
    public function show(string $id): JsonResponse
    {
        $bookId = \Packages\Domain\Book\Domain\ValueObjects\BookId::fromString($id);
        $book = $this->bookRepository->find($bookId);

        return (new BookResource($book))->response();
    }
}
